<?php

if (! function_exists('getCurrentWarehouseId')) {
    /**
     * Lấy warehouse_id hiện tại từ context request đã được middleware gắn vào.
     */
    function getCurrentWarehouseId(): ?int
    {
        $warehouseId = request()->input('current_warehouse_id')
            ?? request()->header('X-Warehouse-Id');

        return $warehouseId ? (int) $warehouseId : null;
    }
}
