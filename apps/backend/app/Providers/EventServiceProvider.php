<?php

namespace App\Providers;

use App\Events\LowStockAlert;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\StockUpdated;
use App\Listeners\LogLowStockAlert;
use App\Listeners\NotifyLowStock;
use App\Listeners\RestoreStockOnOrderCancelled;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\UpdateInventoryOnOrderCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderCreated::class => [
            UpdateInventoryOnOrderCreated::class,
            SendOrderConfirmationEmail::class,
        ],
        OrderCancelled::class => [
            RestoreStockOnOrderCancelled::class,
        ],
        StockUpdated::class => [
            NotifyLowStock::class,
        ],
        LowStockAlert::class => [
            LogLowStockAlert::class,
        ],
    ];
}
