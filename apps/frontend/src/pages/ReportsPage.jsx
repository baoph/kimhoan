import { useEffect, useState } from 'react';
import { reportsApi } from '../api/services';
import { formatCurrency, formatNumber } from '../utils/format';
import { toast } from 'react-toastify';

export default function ReportsPage() {
  const [startDate, setStartDate] = useState(new Date(new Date().setDate(1)).toISOString().slice(0, 10));
  const [endDate, setEndDate] = useState(new Date().toISOString().slice(0, 10));
  const [salesData, setSalesData] = useState(null);
  const [inventoryData, setInventoryData] = useState(null);
  const [lowStockOnly, setLowStockOnly] = useState(false);

  const loadReports = async () => {
    try {
      const [salesRes, inventoryRes] = await Promise.all([
        reportsApi.sales({ start_date: startDate, end_date: endDate }),
        reportsApi.inventory({ low_stock_only: lowStockOnly }),
      ]);
      setSalesData(salesRes.data.data);
      setInventoryData(inventoryRes.data.data);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không tải được báo cáo');
    }
  };

  useEffect(() => {
    loadReports();
  }, [startDate, endDate, lowStockOnly]);

  return (
    <div className="space-y-4">
      <div className="rounded-xl bg-white p-4 shadow-card">
        <h2 className="mb-3 text-xl font-semibold">Báo cáo</h2>
        <div className="flex flex-wrap gap-3">
          <input type="date" className="rounded border px-3 py-2" value={startDate} onChange={(e) => setStartDate(e.target.value)} />
          <input type="date" className="rounded border px-3 py-2" value={endDate} onChange={(e) => setEndDate(e.target.value)} />
          <label className="flex items-center gap-2 rounded border px-3 py-2 text-sm">
            <input type="checkbox" checked={lowStockOnly} onChange={(e) => setLowStockOnly(e.target.checked)} />
            Chỉ hàng sắp hết
          </label>
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <div className="rounded-xl bg-white p-4 shadow-card">
          <h3 className="mb-3 font-semibold">Báo cáo bán hàng</h3>
          {salesData?.summary ? (
            <div className="space-y-2 text-sm">
              <p>Tổng đơn: {formatNumber(salesData.summary.total_orders)}</p>
              <p>Doanh thu gộp: {formatCurrency(salesData.summary.gross_revenue)}</p>
              <p>Tổng giảm giá: {formatCurrency(salesData.summary.total_discount)}</p>
              <p className="font-semibold">Doanh thu thuần: {formatCurrency(salesData.summary.net_revenue)}</p>
            </div>
          ) : (
            <p className="text-sm text-slate-500">Chưa có dữ liệu</p>
          )}
        </div>

        <div className="rounded-xl bg-white p-4 shadow-card">
          <h3 className="mb-3 font-semibold">Báo cáo tồn kho</h3>
          {inventoryData?.summary ? (
            <div className="space-y-2 text-sm">
              <p>Tổng sản phẩm: {formatNumber(inventoryData.summary.total_products)}</p>
              <p>Tổng tồn kho: {formatNumber(inventoryData.summary.total_stock_quantity)}</p>
              <p>Giá trị tồn theo vốn: {formatCurrency(inventoryData.summary.total_stock_value_by_cost)}</p>
              <p>Giá trị tồn theo giá bán: {formatCurrency(inventoryData.summary.total_stock_value_by_price)}</p>
              <p className="font-semibold text-red-600">Số mặt hàng sắp hết: {formatNumber(inventoryData.summary.low_stock_count)}</p>
            </div>
          ) : (
            <p className="text-sm text-slate-500">Chưa có dữ liệu</p>
          )}
        </div>
      </div>

      <div className="rounded-xl bg-white p-4 shadow-card">
        <h3 className="mb-3 font-semibold">Danh sách tồn kho</h3>
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-left">
              <tr>
                <th className="px-3 py-2">Mã hàng</th>
                <th className="px-3 py-2">Tên hàng</th>
                <th className="px-3 py-2">Tồn kho</th>
                <th className="px-3 py-2">Giá vốn</th>
                <th className="px-3 py-2">Giá bán</th>
              </tr>
            </thead>
            <tbody>
              {inventoryData?.products?.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-3 py-2">{item.product_code}</td>
                  <td className="px-3 py-2">{item.name}</td>
                  <td className="px-3 py-2">{formatNumber(item.stock_quantity)}</td>
                  <td className="px-3 py-2">{formatCurrency(item.cost_price)}</td>
                  <td className="px-3 py-2">{formatCurrency(item.selling_price)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
