<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function salesReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $orders = Order::query()
            ->with(['customer:id,name,customer_code', 'staff:id,name'])
            ->whereDate('order_date', '>=', $startDate)
            ->whereDate('order_date', '<=', $endDate)
            ->orderByDesc('order_date')
            ->get();

        $summary = [
            'total_orders' => $orders->count(),
            'gross_revenue' => (float) $orders->sum('total_amount'),
            'total_discount' => (float) $orders->sum('discount'),
            'net_revenue' => (float) $orders->sum('final_amount'),
        ];

        return $this->successResponse([
            'summary' => $summary,
            'orders' => $orders,
        ], 'Lấy báo cáo bán hàng thành công');
    }

    public function inventoryReport(Request $request)
    {
        $query = Product::query()->with(['category:id,name', 'brand:id,name']);

        if ($request->boolean('low_stock_only')) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock');
        }

        $products = $query->orderBy('name')->get();

        $summary = [
            'total_products' => $products->count(),
            'total_stock_quantity' => (int) $products->sum('stock_quantity'),
            'total_stock_value_by_cost' => (float) $products->sum(fn ($item) => $item->stock_quantity * $item->cost_price),
            'total_stock_value_by_price' => (float) $products->sum(fn ($item) => $item->stock_quantity * $item->selling_price),
            'low_stock_count' => $products->filter(fn ($item) => $item->stock_quantity <= $item->min_stock)->count(),
        ];

        return $this->successResponse([
            'summary' => $summary,
            'products' => $products,
        ], 'Lấy báo cáo tồn kho thành công');
    }
}
