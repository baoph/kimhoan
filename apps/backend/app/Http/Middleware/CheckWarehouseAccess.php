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
        // Các route này được gọi trước khi frontend có warehouse context.
        $excludedRoutes = [
            'api/v1/warehouses',
            'api/v1/auth/login',
            'api/v1/auth/logout',
            'api/v1/auth/user',
            'api/v1/auth/profile',
            'api/v1/user',
        ];

        $currentPath = $request->path();
        if (in_array($currentPath, $excludedRoutes, true)) {
            return $next($request);
        }

        $warehouseId = $request->header('X-Warehouse-Id');

        if (! $warehouseId || ! ctype_digit((string) $warehouseId)) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu hoặc sai định dạng header X-Warehouse-Id',
            ], Response::HTTP_BAD_REQUEST);
        }

        $warehouse = Warehouse::query()
            ->whereKey((int) $warehouseId)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Kho không tồn tại',
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
