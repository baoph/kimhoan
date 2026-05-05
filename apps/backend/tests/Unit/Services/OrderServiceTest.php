<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_successfully(): void
    {
        $warehouse = Warehouse::factory()->create();
        $customer = Customer::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create([
            'selling_price' => 100,
            'stock_quantity' => 100,
        ]);

        WarehouseStock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
        ]);

        $orderData = [
            'order_code' => 'DH-TEST-001',
            'customer_id' => $customer->id,
            'order_date' => now()->toISOString(),
            'payment_status' => PaymentStatus::PENDING->value,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ];

        /** @var OrderService $service */
        $service = app(OrderService::class);

        $order = $service->createOrder($orderData, $warehouse->id, null);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(OrderStatus::DRAFT, $order->order_status);
        $this->assertEquals(500, (float) $order->total_amount);
        $this->assertEquals(1, $order->orderItems->count());

        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(95, (int) $stock->quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'transaction_type' => 'sale',
            'quantity' => -5,
            'reference_id' => $order->id,
        ]);
    }

    public function test_create_order_fails_with_insufficient_stock(): void
    {
        $this->expectException(ValidationException::class);

        $warehouse = Warehouse::factory()->create();
        $customer = Customer::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create([
            'stock_quantity' => 1,
            'selling_price' => 100,
        ]);

        WarehouseStock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
        ]);

        /** @var OrderService $service */
        $service = app(OrderService::class);

        $service->createOrder([
            'order_code' => 'DH-TEST-002',
            'customer_id' => $customer->id,
            'order_date' => now()->toISOString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ], $warehouse->id, null);
    }

    public function test_cancel_order_restores_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $customer = Customer::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create([
            'selling_price' => 100,
            'stock_quantity' => 100,
        ]);

        WarehouseStock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
        ]);

        /** @var OrderService $service */
        $service = app(OrderService::class);

        $order = $service->createOrder([
            'order_code' => 'DH-TEST-003',
            'customer_id' => $customer->id,
            'order_date' => now()->toISOString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ], $warehouse->id, null);

        $cancelled = $service->cancelOrder($order);

        $this->assertEquals(OrderStatus::CANCELLED, $cancelled->order_status);

        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(100, (int) $stock->quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'transaction_type' => 'sale_return',
            'quantity' => 10,
            'reference_id' => $order->id,
        ]);
    }
}
