<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $dungCu = Category::query()->updateOrCreate(
            ['name' => 'Dụng cụ', 'parent_id' => null],
            ['description' => 'Nhóm dụng cụ kim hoàn']
        );

        $vatTu = Category::query()->updateOrCreate(
            ['name' => 'Vật tư', 'parent_id' => null],
            ['description' => 'Nhóm vật tư tiêu hao']
        );

        Category::query()->updateOrCreate(
            ['name' => 'Khuôn', 'parent_id' => $dungCu->id],
            ['description' => 'Các loại khuôn đúc']
        );

        Category::query()->updateOrCreate(
            ['name' => 'Dao cắt', 'parent_id' => $dungCu->id],
            ['description' => 'Dao và phụ kiện cắt']
        );

        Category::query()->updateOrCreate(
            ['name' => 'Bao bì', 'parent_id' => $vatTu->id],
            ['description' => 'Hộp, túi, tem nhãn']
        );
    }
}
