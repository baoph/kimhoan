<?php

namespace App\Http\Middleware;

use App\Models\Warehouse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWarehouseAccess
{
    /**
     * Bắt buộc mọi API nghiệp vụ phải có context kho qua header X-Warehouse-Id.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $warehouseId = $request->header('X-Warehouse-Id');

        if (! $warehouseId || ! ctype_digit((string) $warehouseId)) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu hoặc sai định dạng header X-Warehouse-Id',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $warehouse = Warehouse::query()
            ->whereKey((int) $warehouseId)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Kho được chọn không tồn tại hoặc đã ngừng hoạt động',
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        if (! $user || ! $user->hasAccessToWarehouse((int) $warehouseId)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập kho này',
            ], Response::HTTP_FORBIDDEN);
        }

        // Gắn context kho hiện tại để toàn bộ controller dùng chung.
        $request->merge(['current_warehouse_id' => (int) $warehouseId]);

        return $next($request);
    }
}
