<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@kimhoan.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'phone' => '0900000001',
                'is_active' => true,
            ]
        );

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@kimhoan.local'],
            [
                'name' => 'Quản lý Kho 42',
                'password' => Hash::make('12345678'),
                'role' => 'manager',
                'phone' => '0900000003',
                'is_active' => true,
            ]
        );

        $staff = User::query()->updateOrCreate(
            ['email' => 'staff@kimhoan.local'],
            [
                'name' => 'Nhân viên Bán hàng',
                'password' => Hash::make('12345678'),
                'role' => 'staff',
                'phone' => '0900000002',
                'is_active' => true,
            ]
        );

        $warehouseIds = Warehouse::query()->pluck('id');
        $defaultWarehouseId = $warehouseIds->first();

        if ($defaultWarehouseId) {
            $admin->warehouses()->syncWithoutDetaching($warehouseIds->all());
            $manager->warehouses()->syncWithoutDetaching([$defaultWarehouseId]);
            $staff->warehouses()->syncWithoutDetaching([$defaultWarehouseId]);
        }
    }
}
