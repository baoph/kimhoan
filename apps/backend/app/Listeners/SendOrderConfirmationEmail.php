<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmail
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order->loadMissing('customer');

        if (! $order->customer?->email) {
            return;
        }

        // Placeholder cho tích hợp mail queue thực tế.
        Log::info('Order confirmation email queued', [
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'customer_email' => $order->customer->email,
        ]);
    }
}
