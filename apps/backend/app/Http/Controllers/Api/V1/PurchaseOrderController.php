<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $warehouseId = (int) getCurrentWarehouseId();

            $query = PurchaseOrder::query()
                ->with(['warehouse', 'supplier', 'creator'])
                ->where('warehouse_id', $warehouseId);

            if ($search = $request->string('search')->toString()) {
                $query->where('po_code', 'like', "%{$search}%");
            }

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($supplierId = $request->input('supplier_id')) {
                $query->where('supplier_id', $supplierId);
            }

            $purchaseOrders = $query->latest('order_date')->paginate(min((int) $request->input('per_page', 15), 100));

            return $this->paginatedResponse($purchaseOrders, 'Lấy danh sách phiếu nhập thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy danh sách phiếu nhập', ['error' => $e->getMessage()], 500);
        }
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        try {
            $warehouseId = (int) getCurrentWarehouseId();

            $purchaseOrder = DB::transaction(function () use ($request, $warehouseId) {
                $data = $request->validated();
                $items = $data['items'];
                unset($data['items']);

                if (in_array($data['status'] ?? 'draft', ['completed', 'cancelled'], true)) {
                    throw ValidationException::withMessages([
                        'status' => ['Không thể tạo mới ở trạng thái completed/cancelled. Vui lòng dùng thao tác hoàn thành hoặc hủy phiếu nhập.'],
                    ]);
                }

                // Global warehouse context: kho lấy từ header, không lấy từ body.
                $data['warehouse_id'] = $warehouseId;
                $data['po_code'] = $this->generatePoCode($data['order_date']);
                $data['created_by'] = $request->user()->id;
                $data['total_amount'] = $this->calculateTotalAmount($items);
                $data['status'] = $data['status'] ?? 'draft';

                $purchaseOrder = PurchaseOrder::create($data);
                $this->syncItems($purchaseOrder, $items);

                return $purchaseOrder;
            });

            return $this->successResponse(
                $purchaseOrder->load(['warehouse', 'supplier', 'creator', 'items.product']),
                'Tạo phiếu nhập thành công',
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse('Dữ liệu gửi lên không hợp lệ', $e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể tạo phiếu nhập', ['error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            if ($response = $this->ensurePurchaseOrderInWarehouse($request, $purchaseOrder)) {
                return $response;
            }

            return $this->successResponse(
                $purchaseOrder->load(['warehouse', 'supplier', 'creator', 'items.product']),
                'Lấy chi tiết phiếu nhập thành công'
            );
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy chi tiết phiếu nhập', ['error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            if ($response = $this->ensurePurchaseOrderInWarehouse($request, $purchaseOrder)) {
                return $response;
            }

            if (in_array($purchaseOrder->status, ['completed', 'cancelled'], true)) {
                return $this->errorResponse('Chỉ có thể cập nhật phiếu nhập ở trạng thái draft/pending', [], 422);
            }

            DB::transaction(function () use ($request, $purchaseOrder) {
                $data = $request->validated();

                if (isset($data['status']) && in_array($data['status'], ['completed', 'cancelled'], true)) {
                    throw ValidationException::withMessages([
                        'status' => ['Không thể cập nhật trực tiếp sang completed/cancelled. Vui lòng dùng API complete/cancel.'],
                    ]);
                }

                if (isset($data['items'])) {
                    $items = $data['items'];
                    unset($data['items']);

                    $purchaseOrder->items()->delete();
                    $this->syncItems($purchaseOrder, $items);
                    $data['total_amount'] = $this->calculateTotalAmount($items);
                }

                // Không cho phép thay đổi kho từ request body.
                unset($data['warehouse_id']);

                $purchaseOrder->update($data);
            });

            return $this->successResponse(
                $purchaseOrder->fresh()->load(['warehouse', 'supplier', 'creator', 'items.product']),
                'Cập nhật phiếu nhập thành công'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse('Dữ liệu gửi lên không hợp lệ', $e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể cập nhật phiếu nhập', ['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            if ($response = $this->ensurePurchaseOrderInWarehouse($request, $purchaseOrder)) {
                return $response;
            }

            if ($purchaseOrder->status !== 'draft') {
                return $this->errorResponse('Chỉ được xóa phiếu nhập ở trạng thái draft', [], 422);
            }

            DB::transaction(function () use ($purchaseOrder) {
                $purchaseOrder->items()->delete();
                $purchaseOrder->delete();
            });

            return $this->successResponse(null, 'Xóa phiếu nhập thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể xóa phiếu nhập', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hoàn thành phiếu nhập: cộng tồn kho theo kho + tổng tồn sản phẩm + ghi nhận transaction nhập hàng.
     */
    public function complete(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            if ($response = $this->ensurePurchaseOrderInWarehouse($request, $purchaseOrder)) {
                return $response;
            }

            if (! in_array($purchaseOrder->status, ['draft', 'pending'], true)) {
                return $this->errorResponse('Chỉ có thể hoàn thành phiếu nhập ở trạng thái draft/pending', [], 422);
            }

            DB::transaction(function () use ($purchaseOrder) {
                $purchaseOrder->loadMissing('items');

                foreach ($purchaseOrder->items as $item) {
                    $stock = WarehouseStock::query()->firstOrCreate(
                        [
                            'warehouse_id' => $purchaseOrder->warehouse_id,
                            'product_id' => $item->product_id,
                        ],
                        ['quantity' => 0]
                    );

                    $stock->increment('quantity', $item->quantity);

                    $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                    $product->increment('stock_quantity', $item->quantity);

                    InventoryTransaction::create([
                        'warehouse_id' => $purchaseOrder->warehouse_id,
                        'product_id' => $item->product_id,
                        'transaction_type' => 'purchase',
                        'quantity' => $item->quantity,
                        'reference_id' => $purchaseOrder->id,
                        'notes' => 'Nhập hàng PO: '.$purchaseOrder->po_code,
                    ]);
                }

                $purchaseOrder->update(['status' => 'completed']);
            });

            return $this->successResponse($purchaseOrder->fresh()->load(['items.product']), 'Hoàn thành phiếu nhập thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể hoàn thành phiếu nhập', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hủy phiếu nhập đã hoàn thành: trừ tồn kho, trừ tổng tồn và ghi transaction hủy nhập.
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            if ($response = $this->ensurePurchaseOrderInWarehouse($request, $purchaseOrder)) {
                return $response;
            }

            if ($purchaseOrder->status === 'cancelled') {
                return $this->errorResponse('Phiếu nhập đã ở trạng thái hủy', [], 422);
            }

            DB::transaction(function () use ($purchaseOrder) {
                $purchaseOrder->loadMissing('items');

                if ($purchaseOrder->status === 'completed') {
                    foreach ($purchaseOrder->items as $item) {
                        $stock = WarehouseStock::query()
                            ->where('warehouse_id', $purchaseOrder->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->lockForUpdate()
                            ->first();

                        if (! $stock || $stock->quantity < $item->quantity) {
                            throw ValidationException::withMessages([
                                'stock' => ["Không đủ tồn kho tại kho để hủy phiếu {$purchaseOrder->po_code} (SP #{$item->product_id})."],
                            ]);
                        }

                        $stock->decrement('quantity', $item->quantity);

                        $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                        if ($product->stock_quantity < $item->quantity) {
                            throw ValidationException::withMessages([
                                'stock' => ["Không đủ tổng tồn để hủy phiếu {$purchaseOrder->po_code} (SP {$product->name})."],
                            ]);
                        }

                        $product->decrement('stock_quantity', $item->quantity);

                        InventoryTransaction::create([
                            'warehouse_id' => $purchaseOrder->warehouse_id,
                            'product_id' => $item->product_id,
                            'transaction_type' => 'purchase_cancel',
                            'quantity' => -$item->quantity,
                            'reference_id' => $purchaseOrder->id,
                            'notes' => 'Hủy nhập hàng PO: '.$purchaseOrder->po_code,
                        ]);
                    }
                }

                $purchaseOrder->update(['status' => 'cancelled']);
            });

            return $this->successResponse($purchaseOrder->fresh()->load(['items.product']), 'Hủy phiếu nhập thành công');
        } catch (ValidationException $e) {
            return $this->errorResponse('Không thể hủy phiếu nhập', $e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể hủy phiếu nhập', ['error' => $e->getMessage()], 500);
        }
    }

    private function ensurePurchaseOrderInWarehouse(Request $request, PurchaseOrder $purchaseOrder)
    {
        $warehouseId = (int) ($request->input('current_warehouse_id') ?? getCurrentWarehouseId());

        if ((int) $purchaseOrder->warehouse_id !== $warehouseId) {
            return $this->errorResponse('Bạn không có quyền thao tác phiếu nhập của kho khác', [], 403);
        }

        return null;
    }

    private function syncItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        foreach ($items as $item) {
            $purchaseOrder->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    }

    private function calculateTotalAmount(array $items): float
    {
        return (float) collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
    }

    private function generatePoCode(string $orderDate): string
    {
        $datePart = Carbon::parse($orderDate)->format('Ymd');
        $prefix = 'PN-'.$datePart.'-';

        $latestCode = PurchaseOrder::query()
            ->where('po_code', 'like', $prefix.'%')
            ->latest('id')
            ->value('po_code');

        $nextNumber = 1;
        if ($latestCode) {
            $parts = explode('-', $latestCode);
            $nextNumber = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
