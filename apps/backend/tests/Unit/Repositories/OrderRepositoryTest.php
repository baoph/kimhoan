<?php

namespace Tests\Unit\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Warehouse;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_by_warehouse_returns_only_matching_orders(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        Order::factory()->count(2)->create(['warehouse_id' => $warehouseA->id]);
        Order::factory()->count(1)->create(['warehouse_id' => $warehouseB->id]);

        /** @var OrderRepository $repository */
        $repository = app(OrderRepository::class);
        $orders = $repository->getByWarehouse($warehouseA->id);

        $this->assertCount(2, $orders);
        $this->assertTrue($orders->every(fn (Order $order) => (int) $order->warehouse_id === $warehouseA->id));
    }

    public function test_get_pending_orders_returns_draft_and_confirmed(): void
    {
        $warehouse = Warehouse::factory()->create();

        Order::factory()->create([
            'warehouse_id' => $warehouse->id,
            'order_status' => OrderStatus::DRAFT,
        ]);

        Order::factory()->create([
            'warehouse_id' => $warehouse->id,
            'order_status' => OrderStatus::CONFIRMED,
        ]);

        Order::factory()->create([
            'warehouse_id' => $warehouse->id,
            'order_status' => OrderStatus::COMPLETED,
        ]);

        /** @var OrderRepository $repository */
        $repository = app(OrderRepository::class);
        $orders = $repository->getPendingOrders($warehouse->id);

        $this->assertCount(2, $orders);
        $statuses = $orders->pluck('order_status')->map(fn (OrderStatus $status) => $status->value)->all();

        $this->assertContains(OrderStatus::DRAFT->value, $statuses);
        $this->assertContains(OrderStatus::CONFIRMED->value, $statuses);
        $this->assertNotContains(OrderStatus::COMPLETED->value, $statuses);
    }
}
