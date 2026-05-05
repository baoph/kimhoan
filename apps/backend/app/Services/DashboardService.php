<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly CustomerRepository $customerRepository
    ) {}

    public function getWarehouseDashboard(int $warehouseId): array
    {
        return [
            'today_revenue' => $this->getTodayRevenue($warehouseId),
            'today_orders' => $this->getTodayOrdersCount($warehouseId),
            'pending_orders' => $this->getPendingOrdersCount($warehouseId),
            'low_stock_products' => $this->getLowStockCount($warehouseId),
            'top_products' => $this->getTopSellingProducts($warehouseId),
            'top_customers' => $this->customerRepository->getTopCustomers(10, $warehouseId),
            'recent_orders' => $this->getRecentOrders($warehouseId),
        ];
    }

    private function getTodayRevenue(int $warehouseId): float
    {
        return (float) $this->orderRepository->getTodayOrders($warehouseId)
            ->where('order_status', OrderStatus::COMPLETED)
            ->sum('final_amount');
    }

    private function getTodayOrdersCount(int $warehouseId): int
    {
        return $this->orderRepository->getTodayOrders($warehouseId)->count();
    }

    private function getPendingOrdersCount(int $warehouseId): int
    {
        return $this->orderRepository->getPendingOrders($warehouseId)->count();
    }

    private function getLowStockCount(int $warehouseId): int
    {
        return DB::table('warehouse_stock')
            ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
            ->where('warehouse_stock.warehouse_id', $warehouseId)
            ->whereColumn('warehouse_stock.quantity', '<=', 'products.min_stock')
            ->count();
    }

    private function getTopSellingProducts(int $warehouseId, int $limit = 5)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.warehouse_id', $warehouseId)
            ->where('orders.order_status', OrderStatus::COMPLETED->value)
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    private function getRecentOrders(int $warehouseId, int $limit = 10)
    {
        return $this->orderRepository->getByWarehouse($warehouseId, ['customer', 'staff'])
            ->take($limit)
            ->values();
    }
}
