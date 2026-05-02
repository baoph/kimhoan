<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getTodayStats()
    {
        $today = now()->startOfDay();

        $revenue = Order::where('order_date', '>=', $today)
            ->where('order_status', 'completed')
            ->sum('final_amount');

        $returns = Order::where('order_date', '>=', $today)
            ->where('order_status', 'returned')
            ->sum('final_amount');

        $ordersCount = Order::where('order_date', '>=', $today)->count();

        return $this->successResponse([
            'revenue' => (float) $revenue,
            'returns' => (float) $returns,
            'orders_count' => $ordersCount,
        ], 'Lấy thống kê hôm nay thành công');
    }

    public function getTopSellingProducts()
    {
        $products = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_revenue'))
            ->with('product:id,product_code,name')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return $this->successResponse($products, 'Lấy top sản phẩm bán chạy thành công');
    }

    public function getTopCustomers()
    {
        $customers = Order::query()
            ->select('customer_id', DB::raw('SUM(final_amount) as total_spent'), DB::raw('COUNT(id) as total_orders'))
            ->with('customer:id,customer_code,name,phone1')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        return $this->successResponse($customers, 'Lấy top khách hàng mua nhiều thành công');
    }

    public function getRevenueChart(Request $request)
    {
        $type = $request->input('type', 'day');

        $dateFormat = match ($type) {
            'week' => '%x-%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $chart = Order::query()
            ->selectRaw("DATE_FORMAT(order_date, '{$dateFormat}') as label, SUM(final_amount) as revenue")
            ->whereIn('order_status', ['completed', 'confirmed'])
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return $this->successResponse($chart, 'Lấy dữ liệu biểu đồ doanh thu thành công');
    }
}
