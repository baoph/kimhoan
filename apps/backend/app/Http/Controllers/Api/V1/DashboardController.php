<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getTodayStats(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();
        $today = now()->startOfDay();

        $revenue = Order::query()
            ->where('warehouse_id', $warehouseId)
            ->where('order_date', '>=', $today)
            ->where('order_status', 'completed')
            ->sum('final_amount');

        $returns = Order::query()
            ->where('warehouse_id', $warehouseId)
            ->where('order_date', '>=', $today)
            ->where('order_status', 'returned')
            ->sum('final_amount');

        $ordersCount = Order::query()
            ->where('warehouse_id', $warehouseId)
            ->where('order_date', '>=', $today)
            ->count();

        return $this->successResponse([
            'warehouse_id' => $warehouseId,
            'revenue' => (float) $revenue,
            'returns' => (float) $returns,
            'orders_count' => $ordersCount,
        ], 'Lấy thống kê hôm nay thành công');
    }

    public function getTopSellingProducts(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();

        $products = OrderItem::query()
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as total_quantity'), DB::raw('SUM(order_items.total_price) as total_revenue'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.warehouse_id', $warehouseId)
            ->with('product:id,product_code,name')
            ->groupBy('order_items.product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return $this->successResponse($products, 'Lấy top sản phẩm bán chạy thành công');
    }

    public function getTopCustomers(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();

        $customers = Order::query()
            ->select('customer_id', DB::raw('SUM(final_amount) as total_spent'), DB::raw('COUNT(id) as total_orders'))
            ->with('customer:id,customer_code,name,phone1')
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        return $this->successResponse($customers, 'Lấy top khách hàng mua nhiều thành công');
    }

    public function getRevenueChart(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();
        $type = $request->input('type', 'day');

        $dateFormat = match ($type) {
            'week' => '%x-%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $chart = Order::query()
            ->selectRaw("DATE_FORMAT(order_date, '{$dateFormat}') as label, SUM(final_amount) as revenue")
            ->where('warehouse_id', $warehouseId)
            ->whereIn('order_status', ['completed', 'confirmed'])
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return $this->successResponse($chart, 'Lấy dữ liệu biểu đồ doanh thu thành công');
    }
}
