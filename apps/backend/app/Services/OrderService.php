<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createOrder(array $data, int $warehouseId, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($data, $warehouseId, $userId) {
            $items = $data['items'];
            unset($data['items']);

            $this->ensureCustomerInWarehouse($data['customer_id'] ?? null, $warehouseId);
            [$products] = $this->ensureStockAvailability($items, $warehouseId);

            $totalAmount = $this->calculateTotalAmount($items, $products);
            $discount = (float) ($data['discount'] ?? 0);

            $order = Order::create([
                ...$data,
                'warehouse_id' => $warehouseId,
                'staff_id' => $data['staff_id'] ?? $userId,
                'order_status' => $data['order_status'] ?? OrderStatus::DRAFT,
                'payment_status' => $data['payment_status'] ?? PaymentStatus::PENDING,
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'final_amount' => max($totalAmount - $discount, 0),
            ]);

            $this->syncOrderItemsAndStock($order, $items, $products, $warehouseId, false);

            return $order->load(['customer', 'warehouse', 'orderItems.product', 'staff']);
        });
    }

    public function updateOrder(Order $order, array $data, int $warehouseId): Order
    {
        return DB::transaction(function () use ($order, $data, $warehouseId) {
            $this->ensureCustomerInWarehouse($data['customer_id'] ?? $order->customer_id, $warehouseId);

            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $this->restoreStock($order, 'Hoàn kho khi cập nhật đơn');
                [$products] = $this->ensureStockAvailability($items, $warehouseId);
                $order->orderItems()->delete();

                $this->syncOrderItemsAndStock($order, $items, $products, $warehouseId, true);

                $totalAmount = $this->calculateTotalAmount($items, $products);
                $discount = (float) ($data['discount'] ?? $order->discount);
                $data['total_amount'] = $totalAmount;
                $data['final_amount'] = max($totalAmount - $discount, 0);
            }

            unset($data['warehouse_id']);

            $order->update($data);

            return $order->fresh(['customer', 'warehouse', 'orderItems.product', 'staff']);
        });
    }

    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            if ($order->order_status === OrderStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'order_status' => ['Không thể hủy đơn hàng đã hoàn thành'],
                ]);
            }

            $this->restoreStock($order, 'Hoàn kho khi hủy đơn');
            $order->update(['order_status' => OrderStatus::CANCELLED]);

            return $order->fresh(['customer', 'warehouse', 'orderItems.product', 'staff']);
        });
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->restoreStock($order, 'Hoàn kho khi xóa đơn');
            $order->delete();
        });
    }

    private function calculateTotalAmount(array $items, $products): float
    {
        $totalAmount = 0;
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $price = $item['unit_price'] ?? $product->selling_price;
            $totalAmount += $price * $item['quantity'];
        }

        return (float) $totalAmount;
    }

    private function ensureCustomerInWarehouse(?int $customerId, int $warehouseId): void
    {
        if (! $customerId) {
            return;
        }

        $isValidCustomer = Customer::query()
            ->whereKey($customerId)
            ->where('warehouse_id', $warehouseId)
            ->exists();

        if (! $isValidCustomer) {
            throw ValidationException::withMessages([
                'customer_id' => ['Khách hàng không thuộc kho đang thao tác.'],
            ]);
        }
    }

    private function restoreStock(Order $order, string $notePrefix): void
    {
        $order->loadMissing('orderItems.product');

        foreach ($order->orderItems as $item) {
            WarehouseStock::query()->firstOrCreate(
                [
                    'warehouse_id' => $order->warehouse_id,
                    'product_id' => $item->product_id,
                ],
                ['quantity' => 0]
            )->increment('quantity', $item->quantity);

            $item->product?->increment('stock_quantity', $item->quantity);

            if ($item->product_id) {
                InventoryTransaction::create([
                    'warehouse_id' => $order->warehouse_id,
                    'product_id' => $item->product_id,
                    'transaction_type' => 'sale_return',
                    'quantity' => $item->quantity,
                    'reference_id' => $order->id,
                    'notes' => $notePrefix.' '.$order->order_code,
                ]);
            }
        }
    }

    private function ensureStockAvailability(array $items, int $warehouseId): array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => ['Một hoặc nhiều sản phẩm không tồn tại.'],
            ]);
        }

        $stocks = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $stock = $stocks->get($item['product_id']);

            $currentWarehouseQty = (int) ($stock->quantity ?? 0);
            if ($currentWarehouseQty < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Sản phẩm {$product->name} không đủ tồn kho tại kho hiện tại. Tồn: {$currentWarehouseQty}"],
                ]);
            }

            if ((int) $product->stock_quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Sản phẩm {$product->name} không đủ tổng tồn kho hệ thống. Tồn: {$product->stock_quantity}"],
                ]);
            }
        }

        return [$products, $stocks];
    }

    private function syncOrderItemsAndStock(Order $order, array $items, $products, int $warehouseId, bool $isAdjustment): void
    {
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $unitPrice = $item['unit_price'] ?? $product->selling_price;
            $lineTotal = $unitPrice * $item['quantity'];

            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
            ]);

            WarehouseStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->decrement('quantity', $item['quantity']);

            $product->decrement('stock_quantity', $item['quantity']);

            InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'transaction_type' => 'sale',
                'quantity' => -$item['quantity'],
                'reference_id' => $order->id,
                'notes' => ($isAdjustment ? 'Điều chỉnh xuất kho từ đơn hàng ' : 'Xuất kho từ đơn hàng ').$order->order_code,
            ]);
        }
    }
}
