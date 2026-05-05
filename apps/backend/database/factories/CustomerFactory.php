<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'customer_code' => 'KH'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'phone1' => fake()->numerify('09########'),
            'phone2' => null,
            'email' => fake()->safeEmail(),
            'facebook' => null,
            'address' => fake()->streetAddress(),
            'district' => fake()->city(),
            'ward' => fake()->streetSuffix(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'birth_date' => fake()->date(),
            'customer_group_id' => null,
            'notes' => fake()->sentence(),
            'created_by' => null,
        ];
    }
}
