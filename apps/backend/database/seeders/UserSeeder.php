<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@kimhoan.local'],
            [
                'name' => 'Quản trị viên',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'phone' => '0900000001',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'staff@kimhoan.local'],
            [
                'name' => 'Nhân viên bán hàng',
                'password' => Hash::make('12345678'),
                'role' => 'staff',
                'phone' => '0900000002',
            ]
        );
    }
}
