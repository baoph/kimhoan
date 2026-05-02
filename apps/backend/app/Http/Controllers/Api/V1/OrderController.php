<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with(['customer', 'staff']);

        if ($search = $request->string('search')->toString()) {
            $query->where('order_code', 'like', "%{$search}%");
        }

        if ($status = $request->input('order_status')) {
            $query->where('order_status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $orders = $query->latest('order_date')->paginate($perPage);

        return $this->paginatedResponse($orders, 'Lấy danh sách đơn hàng thành công');
    }

    public function store(StoreOrderRequest $request)
    {
        $order = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);

            $this->ensureStockAvailability($items);

            $totalAmount = 0;
            foreach ($items as $item) {
                $price = $item['unit_price'] ?? Product::findOrFail($item['product_id'])->selling_price;
                $totalAmount += $price * $item['quantity'];
            }

            $discount = $data['discount'] ?? 0;
            $data['total_amount'] = $totalAmount;
            $data['final_amount'] = max($totalAmount - $discount, 0);
            $data['staff_id'] = $data['staff_id'] ?? $request->user()?->id;

            $order = Order::create($data);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = $item['unit_price'] ?? $product->selling_price;
                $lineTotal = $unitPrice * $item['quantity'];

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);

                $product->decrement('stock_quantity', $item['quantity']);

                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'transaction_type' => 'export',
                    'quantity' => $item['quantity'],
                    'reference_id' => $order->id,
                    'notes' => 'Xuất kho từ đơn hàng '.$order->order_code,
                ]);
            }

            return $order;
        });

        return $this->successResponse(
            $order->load(['customer', 'staff', 'orderItems.product']),
            'Tạo đơn hàng thành công',
            201
        );
    }

    public function show(Order $order)
    {
        return $this->successResponse(
            $order->load(['customer', 'staff', 'orderItems.product']),
            'Lấy chi tiết đơn hàng thành công'
        );
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        DB::transaction(function () use ($request, $order) {
            $data = $request->validated();

            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $this->restoreStock($order);
                $this->ensureStockAvailability($items);

                $order->orderItems()->delete();

                $totalAmount = 0;
                foreach ($items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $unitPrice = $item['unit_price'] ?? $product->selling_price;
                    $lineTotal = $unitPrice * $item['quantity'];
                    $totalAmount += $lineTotal;

                    $order->orderItems()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ]);

                    $product->decrement('stock_quantity', $item['quantity']);

                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'transaction_type' => 'export',
                        'quantity' => $item['quantity'],
                        'reference_id' => $order->id,
                        'notes' => 'Điều chỉnh xuất kho từ đơn hàng '.$order->order_code,
                    ]);
                }

                $discount = $data['discount'] ?? $order->discount;
                $data['total_amount'] = $totalAmount;
                $data['final_amount'] = max($totalAmount - $discount, 0);
            }

            $order->update($data);
        });

        return $this->successResponse(
            $order->fresh()->load(['customer', 'staff', 'orderItems.product']),
            'Cập nhật đơn hàng thành công'
        );
    }

    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order) {
            $this->restoreStock($order);
            $order->delete();
        });

        return $this->successResponse(null, 'Xóa đơn hàng thành công');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'order_status' => ['required', 'in:draft,confirmed,completed,cancelled,returned'],
                'payment_status' => ['nullable', 'in:pending,paid,partial,refunded'],
            ],
            [
                'required' => ':attribute là bắt buộc.',
                'in' => ':attribute không hợp lệ.',
            ],
            [
                'order_status' => 'Trạng thái đơn hàng',
                'payment_status' => 'Trạng thái thanh toán',
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse('Dữ liệu gửi lên không hợp lệ', $validator->errors(), 422);
        }

        $order->update($validator->validated());

        return $this->successResponse($order, 'Cập nhật trạng thái đơn hàng thành công');
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);

            if ($item->product_id) {
                InventoryTransaction::create([
                    'product_id' => $item->product_id,
                    'transaction_type' => 'return',
                    'quantity' => $item->quantity,
                    'reference_id' => $order->id,
                    'notes' => 'Hoàn kho khi cập nhật/xóa đơn '.$order->order_code,
                ]);
            }
        }
    }

    /**
     * Không cho phép bán âm kho theo business flow.
     */
    private function ensureStockAvailability(array $items): void
    {
        foreach ($items as $item) {
            $product = Product::query()->findOrFail($item['product_id']);

            if ($product->stock_quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Sản phẩm {$product->name} không đủ tồn kho. Tồn hiện tại: {$product->stock_quantity}"],
                ]);
            }
        }
    }
}
