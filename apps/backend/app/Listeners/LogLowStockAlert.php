<?php

namespace App\Listeners;

use App\Events\LowStockAlert;
use Illuminate\Support\Facades\Log;

class LogLowStockAlert
{
    public function handle(LowStockAlert $event): void
    {
        Log::warning('Low stock alert', [
            'product_id' => $event->product->id,
            'product_code' => $event->product->product_code,
            'warehouse_id' => $event->warehouseId,
            'current_stock' => $event->currentStock,
            'min_stock' => $event->product->min_stock,
        ]);
    }
}
