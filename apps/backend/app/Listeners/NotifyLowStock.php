<?php

namespace App\Listeners;

use App\Events\LowStockAlert;
use App\Events\StockUpdated;
use App\Models\Product;

class NotifyLowStock
{
    public function handle(StockUpdated $event): void
    {
        $product = Product::query()->find($event->productId);
        if (! $product) {
            return;
        }

        if ($event->currentStock <= (int) $product->min_stock) {
            event(new LowStockAlert(
                product: $product,
                warehouseId: $event->warehouseId,
                currentStock: $event->currentStock,
            ));
        }
    }
}
