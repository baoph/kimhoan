<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => 'WH'.fake()->unique()->numerify('###'),
            'name' => 'Kho '.fake()->city(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'manager_name' => fake()->name(),
            'is_active' => true,
        ];
    }
}
