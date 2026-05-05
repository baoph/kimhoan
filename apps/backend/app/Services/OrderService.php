<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\StockUpdated;
use App\Models\Order;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\WarehouseStockRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly WarehouseStockRepository $warehouseStockRepository,
        private readonly CustomerRepository $customerRepository,
    ) {}

    public function getAllOrders(int $warehouseId): Collection
    {
        return $this->orderRepository->getByWarehouse($warehouseId, [
            'customer', 'warehouse', 'orderItems.product', 'staff',
        ]);
    }

    public function createOrder(array $data, int $warehouseId, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($data, $warehouseId, $userId) {
            $items = $data['items'];
            unset($data['items']);

            $this->ensureCustomerInWarehouse($data['customer_id'] ?? null, $warehouseId);
            [$products] = $this->ensureStockAvailability($items, $warehouseId);

            $totalAmount = $this->calculateTotalAmount($items, $products);
            $discount = (float) ($data['discount'] ?? 0);

            /** @var Order $order */
            $order = $this->orderRepository->create([
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

            $order = $order->load(['customer', 'warehouse', 'orderItems.product', 'staff']);

            event(new OrderCreated($order));

            return $order;
        });
    }

    public function updateOrder(Order $order, array $data, int $warehouseId): Order
    {
        return DB::transaction(function () use ($order, $data, $warehouseId) {
            $this->ensureCustomerInWarehouse($data['customer_id'] ?? $order->customer_id, $warehouseId);

            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $this->restoreStock($order);
                [$products] = $this->ensureStockAvailability($items, $warehouseId);
                $order->orderItems()->delete();

                $this->syncOrderItemsAndStock($order, $items, $products, $warehouseId, true);

                $totalAmount = $this->calculateTotalAmount($items, $products);
                $discount = (float) ($data['discount'] ?? $order->discount);
                $data['total_amount'] = $totalAmount;
                $data['final_amount'] = max($totalAmount - $discount, 0);
            }

            unset($data['warehouse_id']);

            $this->orderRepository->update($order, $data);

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

            $order->loadMissing('orderItems.product');

            $this->orderRepository->update($order, ['order_status' => OrderStatus::CANCELLED]);

            event(new OrderCancelled($order));

            return $order->fresh(['customer', 'warehouse', 'orderItems.product', 'staff']);
        });
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->restoreStock($order);
            $this->orderRepository->delete($order);
        });
    }

    private function calculateTotalAmount(array $items, Collection $products): float
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

        $isValidCustomer = $this->customerRepository->query()
            ->whereKey($customerId)
            ->where('warehouse_id', $warehouseId)
            ->exists();

        if (! $isValidCustomer) {
            throw ValidationException::withMessages([
                'customer_id' => ['Khách hàng không thuộc kho đang thao tác.'],
            ]);
        }
    }

    private function restoreStock(Order $order): void
    {
        $order->loadMissing('orderItems.product');

        foreach ($order->orderItems as $item) {
            $stock = $this->warehouseStockRepository->firstOrCreateForProduct(
                $order->warehouse_id,
                $item->product_id,
                0
            );
            $stock->increment('quantity', $item->quantity);

            $item->product?->increment('stock_quantity', $item->quantity);

            event(new StockUpdated(
                productId: (int) $item->product_id,
                warehouseId: (int) $order->warehouse_id,
                quantity: (int) $item->quantity,
                currentStock: (int) $stock->fresh()->quantity,
            ));
        }
    }

    private function ensureStockAvailability(array $items, int $warehouseId): array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $products = $this->productRepository->getByIdsForUpdate($productIds->all())->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => ['Một hoặc nhiều sản phẩm không tồn tại.'],
            ]);
        }

        $stocks = $this->warehouseStockRepository
            ->getByWarehouseAndProducts($warehouseId, $productIds->all())
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

    private function syncOrderItemsAndStock(Order $order, array $items, Collection $products, int $warehouseId, bool $isAdjustment): void
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

            $stock = $this->warehouseStockRepository
                ->query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->first();

            if ($stock) {
                $stock->decrement('quantity', $item['quantity']);
            }

            $product->decrement('stock_quantity', $item['quantity']);

            event(new StockUpdated(
                productId: (int) $product->id,
                warehouseId: $warehouseId,
                quantity: -((int) $item['quantity']),
                currentStock: (int) ($stock?->fresh()?->quantity ?? 0),
            ));
        }
    }
}
