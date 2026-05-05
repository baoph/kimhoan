<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $productId,
        public int $warehouseId,
        public int $quantity,
        public int $currentStock,
    ) {}
}
