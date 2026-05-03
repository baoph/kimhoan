<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'KHO-TT',
                'name' => 'Kho Trung Tâm HN',
                'address' => 'Hà Nội',
                'phone' => '0240000001',
                'manager_name' => 'Quản lý kho TT',
                'is_active' => true,
            ],
            [
                'code' => 'KHO-CN1',
                'name' => 'Kho Chi Nhánh 1',
                'address' => 'Hà Nội - Chi nhánh 1',
                'phone' => '0240000002',
                'manager_name' => 'Quản lý kho CN1',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::query()->updateOrCreate(['code' => $warehouse['code']], $warehouse);
        }
    }
}
