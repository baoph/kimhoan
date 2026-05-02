<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'SmartPro', 'description' => 'Thương hiệu dụng cụ cơ khí chính xác'],
            ['name' => 'KimHoan Tools', 'description' => 'Thương hiệu nội bộ'],
            ['name' => 'DTC', 'description' => 'Thiết bị hỗ trợ đánh bóng'],
        ];

        foreach ($brands as $brand) {
            Brand::query()->updateOrCreate(['name' => $brand['name']], $brand);
        }
    }
}
