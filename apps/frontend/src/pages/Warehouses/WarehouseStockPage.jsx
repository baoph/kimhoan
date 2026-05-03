import { useEffect, useState } from 'react';
import { FiArrowLeft, FiDownload } from 'react-icons/fi';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import { warehouseService } from '../../api/services';
import Pagination from '../../components/common/Pagination';
import { formatNumber } from '../../utils/format';

function exportStockCsv(data, warehouseName) {
  const headers = ['Mã sản phẩm', 'Tên sản phẩm', 'Số lượng', 'Đơn vị'];
  const rows = data.map((item) => [
    item.product?.product_code || '',
    item.product?.name || '',
    item.quantity ?? 0,
    item.product?.unit || '',
  ]);

  const csvContent = [headers, ...rows]
    .map((row) => row.map((value) => `"${String(value).replaceAll('"', '""')}"`).join(','))
    .join('\n');

  const blob = new Blob([`\uFEFF${csvContent}`], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `ton-kho-${warehouseName || 'kho'}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

export default function WarehouseStockPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const location = useLocation();

  const [warehouse, setWarehouse] = useState(location.state?.warehouse || null);
  const [stocks, setStocks] = useState([]);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);

  const fetchWarehouse = async () => {
    try {
      const res = await warehouseService.getById(id);
      setWarehouse(res.data.data);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể tải thông tin kho');
    }
  };

  const fetchStock = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await warehouseService.getStock(id, {
        page: targetPage,
        per_page: 10,
        search: search || undefined,
      });
      setStocks(res.data.data || []);
      setMeta(res.data.meta);
      setPage(targetPage);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể tải tồn kho');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!warehouse) {
      fetchWarehouse();
    }
  }, [id]);

  useEffect(() => {
    fetchStock(1);
  }, [id, search]);

  return (
    <div className="space-y-4">
      <div className="rounded-xl bg-white p-4 shadow-card">
        <button className="mb-3 inline-flex items-center gap-2 text-sm text-primary" onClick={() => navigate('/warehouses')}>
          <FiArrowLeft /> Quay lại danh sách kho
        </button>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 className="text-xl font-semibold">Tồn kho theo kho</h2>
            <p className="text-slate-600">
              {warehouse ? `${warehouse.code} - ${warehouse.name}` : 'Đang tải thông tin kho...'}
            </p>
            <p className="text-sm text-slate-500">{warehouse?.address || '--'}</p>
          </div>

          <button
            className="inline-flex items-center gap-2 rounded-lg border border-primary px-3 py-2 text-primary"
            onClick={() => exportStockCsv(stocks, warehouse?.name)}
            disabled={stocks.length === 0}
          >
            <FiDownload /> Xuất CSV
          </button>
        </div>
      </div>

      <div className="rounded-xl bg-white shadow-card">
        <div className="border-b p-4">
          <input
            className="w-full rounded-lg border px-3 py-2 md:w-96"
            placeholder="Tìm theo mã hoặc tên sản phẩm"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-left text-slate-600">
              <tr>
                <th className="px-4 py-3">Mã sản phẩm</th>
                <th className="px-4 py-3">Tên sản phẩm</th>
                <th className="px-4 py-3">Số lượng</th>
                <th className="px-4 py-3">Đơn vị</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan="4" className="px-4 py-8 text-center text-slate-500">
                    Đang tải dữ liệu...
                  </td>
                </tr>
              )}
              {!loading && stocks.length === 0 && (
                <tr>
                  <td colSpan="4" className="px-4 py-8 text-center text-slate-500">
                    Chưa có dữ liệu tồn kho
                  </td>
                </tr>
              )}
              {!loading &&
                stocks.map((item) => (
                  <tr key={item.id} className="border-t">
                    <td className="px-4 py-3 font-medium">{item.product?.product_code || '--'}</td>
                    <td className="px-4 py-3">{item.product?.name || '--'}</td>
                    <td className="px-4 py-3">{formatNumber(item.quantity || 0)}</td>
                    <td className="px-4 py-3">{item.product?.unit || '--'}</td>
                  </tr>
                ))}
            </tbody>
          </table>
        </div>

        <Pagination meta={meta} onPageChange={fetchStock} />
      </div>
    </div>
  );
}
