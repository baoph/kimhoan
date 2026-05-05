<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $costPrice = fake()->numberBetween(10000, 500000);

        return [
            'product_code' => 'PRD'.fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->numerify('893##########'),
            'name' => fake()->words(3, true),
            'category_id' => null,
            'brand_id' => null,
            'cost_price' => $costPrice,
            'selling_price' => $costPrice + fake()->numberBetween(5000, 100000),
            'stock_quantity' => fake()->numberBetween(10, 200),
            'min_stock' => fake()->numberBetween(2, 10),
            'max_stock' => fake()->numberBetween(300, 1000),
            'unit' => 'cái',
            'weight' => fake()->randomFloat(3, 0.1, 3),
            'description' => fake()->sentence(),
            'images' => [],
            'status' => true,
        ];
    }
}
