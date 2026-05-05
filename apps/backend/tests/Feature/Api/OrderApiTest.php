<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_orders(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $user->warehouses()->attach($warehouse->id);

        Sanctum::actingAs($user);

        Order::factory()->count(3)->create(['warehouse_id' => $warehouse->id]);
        Order::factory()->create(['warehouse_id' => Warehouse::factory()->create()->id]);

        $response = $this->withHeader('X-Warehouse-Id', (string) $warehouse->id)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_order(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $user->warehouses()->attach($warehouse->id);

        Sanctum::actingAs($user);

        $customer = Customer::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create([
            'stock_quantity' => 50,
            'selling_price' => 100,
        ]);

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $response = $this->withHeader('X-Warehouse-Id', (string) $warehouse->id)
            ->postJson('/api/v1/orders', [
                'order_code' => 'ORD-API-001',
                'customer_id' => $customer->id,
                'order_date' => now()->toISOString(),
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'order_status', 'total_amount', 'final_amount'],
            ]);
    }

    public function test_cannot_create_order_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/orders', []);

        $response->assertStatus(401);
    }
}
