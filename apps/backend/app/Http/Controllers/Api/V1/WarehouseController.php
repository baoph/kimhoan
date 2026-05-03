<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Throwable;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Warehouse::query();

            if ($search = $request->string('search')->toString()) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }

            if (! is_null($request->input('is_active'))) {
                $query->where('is_active', (bool) $request->boolean('is_active'));
            }

            $warehouses = $query->latest()->paginate(min((int) $request->input('per_page', 15), 100));

            return $this->paginatedResponse($warehouses, 'Lấy danh sách kho thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy danh sách kho', ['error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreWarehouseRequest $request)
    {
        try {
            $warehouse = Warehouse::create($request->validated());

            return $this->successResponse($warehouse, 'Tạo kho thành công', 201);
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể tạo kho', ['error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, Warehouse $warehouse)
    {
        try {
            if ($response = $this->ensureWarehouseInContext($request, $warehouse)) {
                return $response;
            }

            return $this->successResponse($warehouse, 'Lấy chi tiết kho thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy chi tiết kho', ['error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        try {
            if ($response = $this->ensureWarehouseInContext($request, $warehouse)) {
                return $response;
            }

            $warehouse->update($request->validated());

            return $this->successResponse($warehouse->fresh(), 'Cập nhật kho thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể cập nhật kho', ['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        try {
            if ($response = $this->ensureWarehouseInContext($request, $warehouse)) {
                return $response;
            }

            $warehouse->delete();

            return $this->successResponse(null, 'Xóa kho thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể xóa kho. Vui lòng kiểm tra dữ liệu liên quan.', ['error' => $e->getMessage()], 422);
        }
    }

    public function stock(Warehouse $warehouse, Request $request)
    {
        try {
            if ($response = $this->ensureWarehouseInContext($request, $warehouse)) {
                return $response;
            }

            $stocks = $warehouse->warehouseStocks()
                ->with('product')
                ->when($request->string('search')->toString(), function ($query, $search) {
                    $query->whereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('product_code', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('quantity')
                ->paginate(min((int) $request->input('per_page', 15), 100));

            return $this->paginatedResponse($stocks, 'Lấy tồn kho theo kho thành công');
        } catch (Throwable $e) {
            return $this->errorResponse('Không thể lấy tồn kho theo kho', ['error' => $e->getMessage()], 500);
        }
    }

    private function ensureWarehouseInContext(Request $request, Warehouse $warehouse)
    {
        $warehouseId = (int) ($request->input('current_warehouse_id') ?? getCurrentWarehouseId());

        if ((int) $warehouse->id !== $warehouseId) {
            return $this->errorResponse('Bạn không có quyền thao tác dữ liệu kho khác với kho đang chọn', [], 403);
        }

        return null;
    }
}
