<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Throwable;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Supplier::query();

            if ($search = $request->string('search')->toString()) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if (! is_null($request->input('is_active'))) {
                $query->where('is_active', (bool) $request->boolean('is_active'));
            }

            $suppliers = $query->latest()->paginate(min((int) $request->input('per_page', 15), 100));

            return $this->paginatedResponse($suppliers, 'Lấy danh sách nhà cung cấp thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy danh sách nhà cung cấp', ['error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreSupplierRequest $request)
    {
        try {
            $supplier = Supplier::create($request->validated());

            return $this->successResponse($supplier, 'Tạo nhà cung cấp thành công', 201);
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể tạo nhà cung cấp', ['error' => $e->getMessage()], 500);
        }
    }

    public function show(Supplier $supplier)
    {
        try {
            return $this->successResponse($supplier, 'Lấy chi tiết nhà cung cấp thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy chi tiết nhà cung cấp', ['error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        try {
            $supplier->update($request->validated());

            return $this->successResponse($supplier->fresh(), 'Cập nhật nhà cung cấp thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể cập nhật nhà cung cấp', ['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();

            return $this->successResponse(null, 'Xóa nhà cung cấp thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể xóa nhà cung cấp. Vui lòng kiểm tra dữ liệu liên quan.', ['error' => $e->getMessage()], 422);
        }
    }

    public function purchaseHistory(Supplier $supplier, Request $request)
    {
        try {
            $warehouseId = (int) getCurrentWarehouseId();

            $history = $supplier->purchaseOrders()
                ->where('warehouse_id', $warehouseId)
                ->with(['warehouse', 'creator'])
                ->latest('order_date')
                ->paginate(min((int) $request->input('per_page', 15), 100));

            return $this->paginatedResponse($history, 'Lấy lịch sử nhập hàng của nhà cung cấp thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy lịch sử nhập hàng', ['error' => $e->getMessage()], 500);
        }
    }
}
