<?php

namespace App\Listeners;

use App\Enums\InventoryTransactionType;
use App\Events\OrderCreated;
use App\Models\InventoryTransaction;

class UpdateInventoryOnOrderCreated
{
    public function handle(OrderCreated $event): void
    {
        $event->order->loadMissing('orderItems');

        foreach ($event->order->orderItems as $item) {
            $exists = InventoryTransaction::query()
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $event->order->warehouse_id)
                ->where('transaction_type', InventoryTransactionType::SALE->value)
                ->where('reference_type', 'order')
                ->where('reference_id', $event->order->id)
                ->exists();

            if ($exists) {
                continue;
            }

            InventoryTransaction::create([
                'product_id' => $item->product_id,
                'warehouse_id' => $event->order->warehouse_id,
                'transaction_type' => InventoryTransactionType::SALE,
                'quantity' => -$item->quantity,
                'reference_type' => 'order',
                'reference_id' => $event->order->id,
                'notes' => "Đơn hàng #{$event->order->order_code}",
            ]);
        }
    }
}
