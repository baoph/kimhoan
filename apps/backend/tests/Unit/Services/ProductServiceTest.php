<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_with_initial_stock_for_selected_warehouse(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        /** @var ProductService $service */
        $service = app(ProductService::class);

        $product = $service->createProduct([
            'product_code' => 'P-1001',
            'name' => 'Sản phẩm test',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'stock_quantity' => 25,
            'status' => true,
        ], $warehouseA->id);

        $stockA = $product->warehouseStocks()->where('warehouse_id', $warehouseA->id)->first();
        $stockB = $product->warehouseStocks()->where('warehouse_id', $warehouseB->id)->first();

        $this->assertNotNull($stockA);
        $this->assertNotNull($stockB);
        $this->assertEquals(25, (int) $stockA->quantity);
        $this->assertEquals(0, (int) $stockB->quantity);
    }

    public function test_update_stock_successfully(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        /** @var ProductService $service */
        $service = app(ProductService::class);

        $stock = $service->updateStock($product->id, $warehouse->id, 70);

        $this->assertEquals(70, (int) $stock->quantity);

        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 70,
        ]);
    }
}
