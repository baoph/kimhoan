<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();

        $query = Order::query()
            ->with(['customer', 'warehouse', 'orderItems.product', 'staff'])
            ->where('warehouse_id', $warehouseId);

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
        $warehouseId = (int) getCurrentWarehouseId();

        $order = DB::transaction(function () use ($request, $warehouseId) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);

            $this->ensureCustomerInWarehouse($data['customer_id'] ?? null, $warehouseId);
            [$products] = $this->ensureStockAvailability($items, $warehouseId);

            $totalAmount = 0;
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $price = $item['unit_price'] ?? $product->selling_price;
                $totalAmount += $price * $item['quantity'];
            }

            $discount = $data['discount'] ?? 0;
            $data['warehouse_id'] = $warehouseId;
            $data['total_amount'] = $totalAmount;
            $data['final_amount'] = max($totalAmount - $discount, 0);
            $data['staff_id'] = $data['staff_id'] ?? $request->user()?->id;

            $order = Order::create($data);

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $unitPrice = $item['unit_price'] ?? $product->selling_price;
                $lineTotal = $unitPrice * $item['quantity'];

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);

                WarehouseStock::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->decrement('quantity', $item['quantity']);

                $product->decrement('stock_quantity', $item['quantity']);

                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'transaction_type' => 'sale',
                    'quantity' => -$item['quantity'],
                    'reference_id' => $order->id,
                    'notes' => 'Xuất kho từ đơn hàng '.$order->order_code,
                ]);
            }

            return $order;
        });

        return $this->successResponse(
            $order->load(['customer', 'staff', 'orderItems.product', 'warehouse']),
            'Tạo đơn hàng thành công',
            201
        );
    }

    public function show(Request $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

        return $this->successResponse(
            $order->load(['customer', 'staff', 'orderItems.product', 'warehouse']),
            'Lấy chi tiết đơn hàng thành công'
        );
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

        DB::transaction(function () use ($request, $order) {
            $data = $request->validated();
            $warehouseId = (int) getCurrentWarehouseId();

            $this->ensureCustomerInWarehouse($data['customer_id'] ?? $order->customer_id, $warehouseId);

            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $this->restoreStock($order);
                [$products] = $this->ensureStockAvailability($items, $warehouseId);

                $order->orderItems()->delete();

                $totalAmount = 0;
                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);
                    $unitPrice = $item['unit_price'] ?? $product->selling_price;
                    $lineTotal = $unitPrice * $item['quantity'];
                    $totalAmount += $lineTotal;

                    $order->orderItems()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ]);

                    WarehouseStock::query()
                        ->where('warehouse_id', $warehouseId)
                        ->where('product_id', $product->id)
                        ->decrement('quantity', $item['quantity']);

                    $product->decrement('stock_quantity', $item['quantity']);

                    InventoryTransaction::create([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                        'transaction_type' => 'sale',
                        'quantity' => -$item['quantity'],
                        'reference_id' => $order->id,
                        'notes' => 'Điều chỉnh xuất kho từ đơn hàng '.$order->order_code,
                    ]);
                }

                $discount = $data['discount'] ?? $order->discount;
                $data['total_amount'] = $totalAmount;
                $data['final_amount'] = max($totalAmount - $discount, 0);
            }

            // Không cho phép thay đổi kho trực tiếp từ request body.
            unset($data['warehouse_id']);

            $order->update($data);
        });

        return $this->successResponse(
            $order->fresh()->load(['customer', 'staff', 'orderItems.product', 'warehouse']),
            'Cập nhật đơn hàng thành công'
        );
    }

    public function destroy(Request $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

        DB::transaction(function () use ($order) {
            $this->restoreStock($order);
            $order->delete();
        });

        return $this->successResponse(null, 'Xóa đơn hàng thành công');
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

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

    private function ensureOrderInWarehouse(Request $request, Order $order)
    {
        $warehouseId = (int) ($request->input('current_warehouse_id') ?? getCurrentWarehouseId());

        if ((int) $order->warehouse_id !== $warehouseId) {
            return $this->errorResponse('Bạn không có quyền thao tác đơn hàng của kho khác', [], 403);
        }

        return null;
    }

    private function ensureCustomerInWarehouse(?int $customerId, int $warehouseId): void
    {
        if (! $customerId) {
            return;
        }

        $isValidCustomer = Customer::query()
            ->whereKey($customerId)
            ->where('warehouse_id', $warehouseId)
            ->exists();

        if (! $isValidCustomer) {
            throw ValidationException::withMessages([
                'customer_id' => ['Khách hàng không thuộc kho đang thao tác.'],
            ]);
        }
    }

    private function restoreStock(Order $order): void
    {
        $order->loadMissing('orderItems.product');

        foreach ($order->orderItems as $item) {
            WarehouseStock::query()->firstOrCreate(
                [
                    'warehouse_id' => $order->warehouse_id,
                    'product_id' => $item->product_id,
                ],
                ['quantity' => 0]
            )->increment('quantity', $item->quantity);

            $item->product?->increment('stock_quantity', $item->quantity);

            if ($item->product_id) {
                InventoryTransaction::create([
                    'warehouse_id' => $order->warehouse_id,
                    'product_id' => $item->product_id,
                    'transaction_type' => 'sale_return',
                    'quantity' => $item->quantity,
                    'reference_id' => $order->id,
                    'notes' => 'Hoàn kho khi cập nhật/xóa đơn '.$order->order_code,
                ]);
            }
        }
    }

    /**
     * Không cho phép bán âm kho theo business flow tại kho hiện tại.
     */
    private function ensureStockAvailability(array $items, int $warehouseId): array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => ['Một hoặc nhiều sản phẩm không tồn tại.'],
            ]);
        }

        $stocks = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $stock = $stocks->get($item['product_id']);

            $currentWarehouseQty = (int) ($stock->quantity ?? 0);
            if ($currentWarehouseQty < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Sản phẩm {$product->name} không đủ tồn kho tại kho hiện tại. Tồn: {$currentWarehouseQty}"],
                ]);
            }

            if ((int) $product->stock_quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Sản phẩm {$product->name} không đủ tổng tồn kho hệ thống. Tồn: {$product->stock_quantity}"],
                ]);
            }
        }

        return [$products, $stocks];
    }
}
