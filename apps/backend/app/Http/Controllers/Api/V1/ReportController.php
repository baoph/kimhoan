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
        $warehouseId = (int) getCurrentWarehouseId();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $orders = Order::query()
            ->with(['customer:id,name,customer_code', 'staff:id,name'])
            ->where('warehouse_id', $warehouseId)
            ->whereDate('order_date', '>=', $startDate)
            ->whereDate('order_date', '<=', $endDate)
            ->orderByDesc('order_date')
            ->get();

        $summary = [
            'warehouse_id' => $warehouseId,
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
        $warehouseId = (int) getCurrentWarehouseId();

        $query = Product::query()->with([
            'category:id,name',
            'brand:id,name',
            'warehouseStocks' => fn ($q) => $q->where('warehouse_id', $warehouseId),
        ]);

        if ($request->boolean('low_stock_only')) {
            $query->whereHas('warehouseStocks', function ($stockQuery) use ($warehouseId) {
                $stockQuery->where('warehouse_id', $warehouseId)
                    ->whereColumn('quantity', '<=', 'products.min_stock');
            });
        }

        $products = $query->orderBy('name')->get()->map(function (Product $product) {
            $stock = $product->warehouseStocks->first();
            $product->setAttribute('current_stock_quantity', (int) ($stock->quantity ?? 0));

            return $product;
        });

        $summary = [
            'warehouse_id' => $warehouseId,
            'total_products' => $products->count(),
            'total_stock_quantity' => (int) $products->sum('current_stock_quantity'),
            'total_stock_value_by_cost' => (float) $products->sum(fn ($item) => $item->current_stock_quantity * $item->cost_price),
            'total_stock_value_by_price' => (float) $products->sum(fn ($item) => $item->current_stock_quantity * $item->selling_price),
            'low_stock_count' => $products->filter(fn ($item) => $item->current_stock_quantity <= $item->min_stock)->count(),
        ];

        return $this->successResponse([
            'summary' => $summary,
            'products' => $products,
        ], 'Lấy báo cáo tồn kho thành công');
    }
}
