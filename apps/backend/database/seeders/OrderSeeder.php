<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->count() > 0) {
            return;
        }

        $products = Product::query()->take(10)->get();
        $customers = Customer::query()->take(10)->get();
        $staffId = User::query()->where('role', 'staff')->value('id')
            ?? User::query()->value('id');

        if ($products->isEmpty() || $customers->isEmpty() || ! $staffId) {
            return;
        }

        DB::transaction(function () use ($products, $customers, $staffId) {
            for ($i = 1; $i <= 5; $i++) {
                $selectedProducts = $products->shuffle()->take(2);
                $totalAmount = 0;

                $order = Order::create([
                    'order_code' => 'DH'.now()->format('ymd').str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'customer_id' => $customers[$i - 1]->id,
                    'staff_id' => $staffId,
                    'order_date' => now()->subDays(6 - $i),
                    'total_amount' => 0,
                    'discount' => 10000 * $i,
                    'final_amount' => 0,
                    'payment_status' => 'paid',
                    'order_status' => 'completed',
                    'notes' => 'Đơn hàng mẫu #'.$i,
                ]);

                foreach ($selectedProducts as $product) {
                    $qty = random_int(1, 3);
                    $lineTotal = $product->selling_price * $qty;
                    $totalAmount += $lineTotal;

                    $order->orderItems()->create([
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $product->selling_price,
                        'total_price' => $lineTotal,
                    ]);

                    $product->decrement('stock_quantity', $qty);

                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'transaction_type' => 'export',
                        'quantity' => $qty,
                        'reference_id' => $order->id,
                        'notes' => 'Xuất kho từ dữ liệu mẫu '.$order->order_code,
                    ]);
                }

                $order->update([
                    'total_amount' => $totalAmount,
                    'final_amount' => max($totalAmount - $order->discount, 0),
                ]);
            }
        });
    }
}
