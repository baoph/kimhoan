<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseStock>
 */
class WarehouseStockFactory extends Factory
{
    protected $model = WarehouseStock::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(0, 200),
        ];
    }
}
