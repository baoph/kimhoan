<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class UserWarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouseIds = Warehouse::query()->pluck('id');
        $defaultWarehouseId = $warehouseIds->first();

        if (! $defaultWarehouseId) {
            return;
        }

        // Admin được gán tất cả kho theo yêu cầu Global Warehouse Context.
        User::query()
            ->where('role', 'admin')
            ->get()
            ->each(fn (User $user) => $user->warehouses()->syncWithoutDetaching($warehouseIds->all()));

        // Nhân viên mặc định được gán kho đầu tiên để có thể thao tác ngay sau khi seed.
        User::query()
            ->where('role', 'staff')
            ->get()
            ->each(fn (User $user) => $user->warehouses()->syncWithoutDetaching([$defaultWarehouseId]));

        // Backfill dữ liệu cũ nếu còn null.
        Customer::query()->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);
        Order::query()->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);
        InventoryTransaction::query()->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);
    }
}
