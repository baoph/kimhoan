<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(100000, 5000000);
        $discount = fake()->numberBetween(0, 100000);

        return [
            'warehouse_id' => Warehouse::factory(),
            'order_code' => 'DH'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'staff_id' => User::factory(),
            'order_date' => now(),
            'total_amount' => $total,
            'discount' => $discount,
            'final_amount' => max($total - $discount, 0),
            'payment_status' => PaymentStatus::PENDING,
            'order_status' => OrderStatus::DRAFT,
            'notes' => fake()->sentence(),
        ];
    }
}
