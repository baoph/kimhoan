<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->pluck('id')->all();
        $brands = Brand::query()->pluck('id')->all();

        $products = [
            ['product_code' => '013004', 'name' => 'Quả cân 2 lượng', 'cost_price' => 150000, 'selling_price' => 175000, 'stock_quantity' => 50],
            ['product_code' => '013003', 'name' => 'Bao giấy trắng T thường', 'cost_price' => 67000, 'selling_price' => 78000, 'stock_quantity' => 80],
            ['product_code' => '013002', 'name' => 'Cuốn in 1M 10x15', 'cost_price' => 11000, 'selling_price' => 14000, 'stock_quantity' => 120],
            ['product_code' => '013001', 'name' => 'Viết thử xoàn Smart Pro', 'cost_price' => 800, 'selling_price' => 1150, 'stock_quantity' => 200],
            ['product_code' => '013000', 'name' => 'Khuôn in hộp bộ D ko viền', 'cost_price' => 300000, 'selling_price' => 345000, 'stock_quantity' => 30],
            ['product_code' => '012999', 'name' => 'Khuôn in hộp bộ D có viền', 'cost_price' => 320000, 'selling_price' => 368000, 'stock_quantity' => 25],
            ['product_code' => '012998', 'name' => 'Hộp bộ D 18x18x6', 'cost_price' => 90000, 'selling_price' => 103500, 'stock_quantity' => 60],
            ['product_code' => '012997', 'name' => 'Túi giấy in L', 'cost_price' => 17000, 'selling_price' => 19500, 'stock_quantity' => 100],
            ['product_code' => '012996', 'name' => 'Khay vòng rãnh cháo 1P5', 'cost_price' => 343000, 'selling_price' => 395000, 'stock_quantity' => 15],
            ['product_code' => '012995', 'name' => 'Khay vòng rãnh cháo', 'cost_price' => 370000, 'selling_price' => 420000, 'stock_quantity' => 10],
        ];

        foreach ($products as $index => $product) {
            Product::query()->updateOrCreate(
                ['product_code' => $product['product_code']],
                $product + [
                    'barcode' => 'BC'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                    'category_id' => $categories[$index % max(count($categories), 1)] ?? null,
                    'brand_id' => $brands[$index % max(count($brands), 1)] ?? null,
                    'min_stock' => 5,
                    'max_stock' => 999999999,
                    'unit' => 'cái',
                    'status' => true,
                ]
            );
        }
    }
}
