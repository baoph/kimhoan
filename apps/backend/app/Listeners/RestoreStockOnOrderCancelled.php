<?php

namespace App\Listeners;

use App\Enums\InventoryTransactionType;
use App\Events\OrderCancelled;
use App\Events\StockUpdated;
use App\Models\InventoryTransaction;
use App\Models\WarehouseStock;

class RestoreStockOnOrderCancelled
{
    public function handle(OrderCancelled $event): void
    {
        $event->order->loadMissing('orderItems.product');

        foreach ($event->order->orderItems as $item) {
            $exists = InventoryTransaction::query()
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $event->order->warehouse_id)
                ->where('transaction_type', InventoryTransactionType::SALE_RETURN->value)
                ->where('reference_type', 'order')
                ->where('reference_id', $event->order->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $stock = WarehouseStock::query()->firstOrCreate(
                [
                    'warehouse_id' => $event->order->warehouse_id,
                    'product_id' => $item->product_id,
                ],
                ['quantity' => 0]
            );

            $stock->increment('quantity', $item->quantity);
            $item->product?->increment('stock_quantity', $item->quantity);

            InventoryTransaction::create([
                'warehouse_id' => $event->order->warehouse_id,
                'product_id' => $item->product_id,
                'transaction_type' => InventoryTransactionType::SALE_RETURN,
                'quantity' => $item->quantity,
                'reference_type' => 'order',
                'reference_id' => $event->order->id,
                'notes' => 'Hoàn kho khi hủy đơn '.$event->order->order_code,
            ]);

            event(new StockUpdated(
                productId: (int) $item->product_id,
                warehouseId: (int) $event->order->warehouse_id,
                quantity: (int) $item->quantity,
                currentStock: (int) $stock->fresh()->quantity,
            ));
        }
    }
}
