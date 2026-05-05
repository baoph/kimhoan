<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $user->warehouses()->attach($warehouse->id);

        Sanctum::actingAs($user);

        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $productA->id,
            'quantity' => 10,
        ]);

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeader('X-Warehouse-Id', (string) $warehouse->id)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_low_stock_products(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $user->warehouses()->attach($warehouse->id);

        Sanctum::actingAs($user);

        $lowProduct = Product::factory()->create(['min_stock' => 5]);
        $okProduct = Product::factory()->create(['min_stock' => 5]);

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $lowProduct->id,
            'quantity' => 4,
        ]);

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $okProduct->id,
            'quantity' => 20,
        ]);

        $response = $this->withHeader('X-Warehouse-Id', (string) $warehouse->id)
            ->getJson('/api/v1/products/low-stock');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }
}
