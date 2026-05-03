import { useEffect, useState } from 'react';
import { dashboardApi } from '../api/services';
import { formatCurrency, formatNumber } from '../utils/format';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { toast } from 'react-toastify';
import { useWarehouse } from '../contexts/WarehouseContext';

export default function DashboardPage() {
  const { currentWarehouse } = useWarehouse();
  const [stats, setStats] = useState({ revenue: 0, returns: 0, orders_count: 0 });
  const [chartType, setChartType] = useState('day');
  const [chartData, setChartData] = useState([]);
  const [topProducts, setTopProducts] = useState([]);
  const [topCustomers, setTopCustomers] = useState([]);

  const loadData = async () => {
    try {
      const [statsRes, chartRes, productRes, customerRes] = await Promise.all([
        dashboardApi.todayStats(),
        dashboardApi.revenueChart(chartType),
        dashboardApi.topSelling(),
        dashboardApi.topCustomers(),
      ]);
      setStats(statsRes.data.data || {});
      setChartData(chartRes.data.data || []);
      setTopProducts(productRes.data.data || []);
      setTopCustomers(customerRes.data.data || []);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không tải được dashboard');
    }
  };

  useEffect(() => {
    loadData();
  }, [chartType]);

  return (
    <div className="space-y-5">
      <div className="rounded-xl bg-white p-4 shadow-card">
        <h2 className="text-xl font-semibold">Tổng quan</h2>
        <p className="mt-1 text-sm text-slate-500">Kho hiện tại: {currentWarehouse?.name || 'Chưa chọn kho'}</p>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <StatCard title="Doanh thu" value={formatCurrency(stats.revenue)} />
        <StatCard title="Trả hàng" value={formatCurrency(stats.returns)} />
        <StatCard title="Số hóa đơn" value={formatNumber(stats.orders_count)} />
      </div>

      <div className="rounded-xl bg-white p-4 shadow-card">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="font-semibold">Biểu đồ doanh thu</h3>
          <select className="rounded border px-3 py-2" value={chartType} onChange={(e) => setChartType(e.target.value)}>
            <option value="day">Theo ngày</option>
            <option value="week">Theo tuần</option>
            <option value="month">Theo tháng</option>
          </select>
        </div>
        <div className="h-80">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="label" />
              <YAxis />
              <Tooltip />
              <Bar dataKey="revenue" fill="#2563EB" />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <SimpleTable
          title="Top 10 hàng bán chạy"
          rows={topProducts.map((x) => ({
            name: x.product?.name || '--',
            value: `${formatNumber(x.total_quantity)} | ${formatCurrency(x.total_revenue)}`,
          }))}
        />
        <SimpleTable
          title="Top 10 khách mua nhiều"
          rows={topCustomers.map((x) => ({
            name: x.customer?.name || '--',
            value: `${formatNumber(x.total_orders)} đơn | ${formatCurrency(x.total_spent)}`,
          }))}
        />
      </div>
    </div>
  );
}

function StatCard({ title, value }) {
  return (
    <div className="rounded-xl bg-white p-4 shadow-card">
      <p className="text-sm text-slate-500">{title}</p>
      <p className="mt-2 text-2xl font-bold text-slate-800">{value}</p>
    </div>
  );
}

function SimpleTable({ title, rows }) {
  return (
    <div className="rounded-xl bg-white p-4 shadow-card">
      <h3 className="mb-3 font-semibold">{title}</h3>
      <div className="space-y-2">
        {rows.length === 0 && <p className="text-sm text-slate-500">Chưa có dữ liệu</p>}
        {rows.map((row, index) => (
          <div key={index} className="flex items-center justify-between rounded bg-slate-50 px-3 py-2 text-sm">
            <span>{row.name}</span>
            <span className="font-medium text-slate-700">{row.value}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
