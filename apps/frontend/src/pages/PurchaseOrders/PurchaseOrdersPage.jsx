import { useEffect, useMemo, useState } from 'react';
import { FiCheckCircle, FiEye, FiPlus, FiXCircle } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { purchaseOrderService } from '../../api/services';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import WarehouseSelect from '../../components/common/WarehouseSelect';
import SupplierSelect from '../../components/common/SupplierSelect';
import { formatCurrency, formatDateOnly } from '../../utils/format';

export default function PurchaseOrdersPage() {
  const navigate = useNavigate();
  const [purchaseOrders, setPurchaseOrders] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);

  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({
    warehouse_id: '',
    supplier_id: '',
    status: '',
    start_date: '',
    end_date: '',
  });

  const [confirmState, setConfirmState] = useState({
    open: false,
    action: null,
    order: null,
    loading: false,
  });

  const fetchPurchaseOrders = async (targetPage = page) => {
    setLoading(true);
    try {
      const params = {
        page: targetPage,
        per_page: 10,
        search: search || undefined,
        warehouse_id: filters.warehouse_id || undefined,
        supplier_id: filters.supplier_id || undefined,
        status: filters.status || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
      };

      const res = await purchaseOrderService.getAll(params);
      setPurchaseOrders(res.data.data || []);
      setMeta(res.data.meta);
      setPage(targetPage);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể tải danh sách phiếu nhập');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPurchaseOrders(1);
  }, [search, filters.warehouse_id, filters.supplier_id, filters.status, filters.start_date, filters.end_date]);

  const displayData = useMemo(() => {
    if (!filters.start_date && !filters.end_date) return purchaseOrders;

    return purchaseOrders.filter((item) => {
      const orderDate = new Date(item.order_date);
      if (filters.start_date) {
        const start = new Date(filters.start_date);
        if (orderDate < start) return false;
      }
      if (filters.end_date) {
        const end = new Date(filters.end_date);
        end.setHours(23, 59, 59, 999);
        if (orderDate > end) return false;
      }
      return true;
    });
  }, [purchaseOrders, filters.start_date, filters.end_date]);

  const openConfirm = (action, order) => {
    setConfirmState({
      open: true,
      action,
      order,
      loading: false,
    });
  };

  const handleConfirm = async () => {
    if (!confirmState.order || !confirmState.action) return;

    setConfirmState((prev) => ({ ...prev, loading: true }));
    try {
      if (confirmState.action === 'complete') {
        await purchaseOrderService.complete(confirmState.order.id);
        toast.success('Hoàn thành phiếu nhập thành công');
      } else if (confirmState.action === 'cancel') {
        await purchaseOrderService.cancel(confirmState.order.id);
        toast.success('Hủy phiếu nhập thành công');
      }

      setConfirmState({ open: false, action: null, order: null, loading: false });
      fetchPurchaseOrders(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Thao tác thất bại');
      setConfirmState((prev) => ({ ...prev, loading: false }));
    }
  };

  const getConfirmContent = () => {
    if (confirmState.action === 'complete') {
      return {
        title: 'Hoàn thành phiếu nhập',
        message: `Bạn có chắc muốn hoàn thành phiếu ${confirmState.order?.po_code}?`,
        confirmText: 'Hoàn thành',
        variant: 'primary',
      };
    }

    return {
      title: 'Hủy phiếu nhập',
      message: `Bạn có chắc muốn hủy phiếu ${confirmState.order?.po_code}?`,
      confirmText: 'Hủy phiếu',
      variant: 'danger',
    };
  };

  const confirmContent = getConfirmContent();

  return (
    <div className="rounded-xl bg-white shadow-card">
      <div className="flex flex-col gap-3 border-b p-4">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <h2 className="text-xl font-semibold">Phiếu nhập hàng</h2>
          <button
            className="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white"
            onClick={() => navigate('/purchase-orders/create')}
          >
            <FiPlus /> Tạo phiếu nhập
          </button>
        </div>

        <div className="grid gap-2 lg:grid-cols-6">
          <input
            className="rounded-lg border px-3 py-2 lg:col-span-2"
            placeholder="Tìm theo mã phiếu"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />

          <WarehouseSelect
            value={filters.warehouse_id}
            includeAllOption
            onChange={(value) => setFilters((prev) => ({ ...prev, warehouse_id: value }))}
          />

          <SupplierSelect
            value={filters.supplier_id}
            includeAllOption
            onChange={(value) => setFilters((prev) => ({ ...prev, supplier_id: value }))}
          />

          <select
            className="rounded-lg border px-3 py-2"
            value={filters.status}
            onChange={(e) => setFilters((prev) => ({ ...prev, status: e.target.value }))}
          >
            <option value="">Tất cả trạng thái</option>
            <option value="draft">Nháp</option>
            <option value="pending">Chờ xử lý</option>
            <option value="completed">Hoàn thành</option>
            <option value="cancelled">Đã hủy</option>
          </select>

          <div className="grid grid-cols-2 gap-2 lg:col-span-2">
            <input
              type="date"
              className="rounded-lg border px-3 py-2"
              value={filters.start_date}
              onChange={(e) => setFilters((prev) => ({ ...prev, start_date: e.target.value }))}
            />
            <input
              type="date"
              className="rounded-lg border px-3 py-2"
              value={filters.end_date}
              onChange={(e) => setFilters((prev) => ({ ...prev, end_date: e.target.value }))}
            />
          </div>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 text-left text-slate-600">
            <tr>
              <th className="px-4 py-3">Mã phiếu</th>
              <th className="px-4 py-3">Kho</th>
              <th className="px-4 py-3">Nhà cung cấp</th>
              <th className="px-4 py-3">Ngày nhập</th>
              <th className="px-4 py-3">Tổng tiền</th>
              <th className="px-4 py-3">Trạng thái</th>
              <th className="px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan="7" className="px-4 py-8 text-center text-slate-500">
                  Đang tải dữ liệu...
                </td>
              </tr>
            )}

            {!loading && displayData.length === 0 && (
              <tr>
                <td colSpan="7" className="px-4 py-8 text-center text-slate-500">
                  Chưa có phiếu nhập nào
                </td>
              </tr>
            )}

            {!loading &&
              displayData.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-4 py-3 font-medium">{item.po_code}</td>
                  <td className="px-4 py-3">{item.warehouse?.name || '--'}</td>
                  <td className="px-4 py-3">{item.supplier?.name || '--'}</td>
                  <td className="px-4 py-3">{formatDateOnly(item.order_date)}</td>
                  <td className="px-4 py-3">{formatCurrency(item.total_amount)}</td>
                  <td className="px-4 py-3">
                    <StatusBadge status={item.status} />
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      <button
                        className="rounded border border-blue-200 p-2 text-blue-600"
                        title="Xem chi tiết"
                        onClick={() => navigate(`/purchase-orders/${item.id}`)}
                      >
                        <FiEye />
                      </button>

                      {['draft', 'pending'].includes(item.status) && (
                        <button
                          className="rounded border border-emerald-200 p-2 text-emerald-600"
                          title="Hoàn thành"
                          onClick={() => openConfirm('complete', item)}
                        >
                          <FiCheckCircle />
                        </button>
                      )}

                      {item.status !== 'cancelled' && (
                        <button
                          className="rounded border border-red-200 p-2 text-red-600"
                          title="Hủy phiếu"
                          onClick={() => openConfirm('cancel', item)}
                        >
                          <FiXCircle />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      <Pagination meta={meta} onPageChange={fetchPurchaseOrders} />

      <ConfirmDialog
        open={confirmState.open}
        onClose={() => setConfirmState({ open: false, action: null, order: null, loading: false })}
        onConfirm={handleConfirm}
        loading={confirmState.loading}
        title={confirmContent.title}
        message={confirmContent.message}
        confirmText={confirmContent.confirmText}
        variant={confirmContent.variant}
      />
    </div>
  );
}
