import { useEffect, useMemo, useState } from 'react';
import { FiArrowLeft, FiCheckCircle, FiEdit2, FiPrinter, FiTrash2, FiXCircle } from 'react-icons/fi';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import { purchaseOrderService } from '../../api/services';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import StatusBadge from '../../components/common/StatusBadge';
import { formatCurrency, formatDateOnly, formatNumber } from '../../utils/format';

export default function PurchaseOrderDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(false);
  const [confirmState, setConfirmState] = useState({ open: false, action: null, loading: false });

  const fetchDetail = async () => {
    setLoading(true);
    try {
      const res = await purchaseOrderService.getById(id);
      setOrder(res.data.data);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể tải chi tiết phiếu nhập');
      navigate('/purchase-orders');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDetail();
  }, [id]);

  const grandTotal = useMemo(() => {
    if (!order?.items) return 0;
    return order.items.reduce((sum, item) => sum + Number(item.total_price || item.quantity * item.unit_price || 0), 0);
  }, [order]);

  const openConfirm = (action) => setConfirmState({ open: true, action, loading: false });

  const handleAction = async () => {
    if (!order || !confirmState.action) return;

    setConfirmState((prev) => ({ ...prev, loading: true }));
    try {
      if (confirmState.action === 'complete') {
        await purchaseOrderService.complete(order.id);
        toast.success('Hoàn thành phiếu nhập thành công');
      } else if (confirmState.action === 'cancel') {
        await purchaseOrderService.cancel(order.id);
        toast.success('Hủy phiếu nhập thành công');
      } else if (confirmState.action === 'delete') {
        await purchaseOrderService.delete(order.id);
        toast.success('Xóa phiếu nhập thành công');
        navigate('/purchase-orders');
        return;
      }

      setConfirmState({ open: false, action: null, loading: false });
      fetchDetail();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Thao tác thất bại');
      setConfirmState((prev) => ({ ...prev, loading: false }));
    }
  };

  const confirmContent = useMemo(() => {
    if (confirmState.action === 'complete') {
      return {
        title: 'Hoàn thành phiếu nhập',
        message: `Bạn có chắc muốn hoàn thành phiếu ${order?.po_code}?`,
        confirmText: 'Hoàn thành',
        variant: 'primary',
      };
    }

    if (confirmState.action === 'delete') {
      return {
        title: 'Xóa phiếu nhập',
        message: `Bạn có chắc muốn xóa phiếu ${order?.po_code}?`,
        confirmText: 'Xóa',
        variant: 'danger',
      };
    }

    return {
      title: 'Hủy phiếu nhập',
      message:
        order?.status === 'completed'
          ? `Phiếu ${order?.po_code} đã hoàn thành. Hủy phiếu sẽ trừ tồn kho. Bạn có chắc muốn tiếp tục?`
          : `Bạn có chắc muốn hủy phiếu ${order?.po_code}?`,
      confirmText: 'Hủy phiếu',
      variant: 'danger',
    };
  }, [confirmState.action, order]);

  if (loading) {
    return <div className="rounded-xl bg-white p-6 text-center text-slate-500 shadow-card">Đang tải chi tiết phiếu nhập...</div>;
  }

  if (!order) return null;

  return (
    <div className="space-y-4">
      <div className="rounded-xl bg-white p-4 shadow-card">
        <button className="mb-3 inline-flex items-center gap-2 text-sm text-primary" onClick={() => navigate('/purchase-orders')}>
          <FiArrowLeft /> Quay lại danh sách phiếu nhập
        </button>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 className="text-xl font-semibold">Chi tiết phiếu nhập {order.po_code}</h2>
            <p className="text-sm text-slate-600">Ngày nhập: {formatDateOnly(order.order_date)}</p>
          </div>
          <StatusBadge status={order.status} />
        </div>

        <div className="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          <div>
            <p className="text-xs text-slate-500">Kho</p>
            <p className="font-medium">{order.warehouse?.name || '--'}</p>
          </div>
          <div>
            <p className="text-xs text-slate-500">Nhà cung cấp</p>
            <p className="font-medium">{order.supplier?.name || '--'}</p>
          </div>
          <div>
            <p className="text-xs text-slate-500">Người tạo</p>
            <p className="font-medium">{order.creator?.name || '--'}</p>
          </div>
        </div>

        <div className="mt-3">
          <p className="text-xs text-slate-500">Ghi chú</p>
          <p>{order.notes || '--'}</p>
        </div>
      </div>

      <div className="rounded-xl bg-white shadow-card">
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-left text-slate-600">
              <tr>
                <th className="px-4 py-3">Mã SP</th>
                <th className="px-4 py-3">Tên sản phẩm</th>
                <th className="px-4 py-3">Số lượng</th>
                <th className="px-4 py-3">Đơn giá</th>
                <th className="px-4 py-3">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              {!order.items?.length && (
                <tr>
                  <td colSpan="5" className="px-4 py-8 text-center text-slate-500">
                    Không có sản phẩm trong phiếu nhập
                  </td>
                </tr>
              )}
              {order.items?.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-4 py-3">{item.product?.product_code || '--'}</td>
                  <td className="px-4 py-3">{item.product?.name || '--'}</td>
                  <td className="px-4 py-3">{formatNumber(item.quantity)}</td>
                  <td className="px-4 py-3">{formatCurrency(item.unit_price)}</td>
                  <td className="px-4 py-3">{formatCurrency(item.total_price)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="border-t p-4 text-right">
          <p className="text-lg font-semibold text-blue-700">Tổng cộng: {formatCurrency(order.total_amount || grandTotal)}</p>
        </div>
      </div>

      <div className="rounded-xl bg-white p-4 shadow-card">
        <div className="flex flex-wrap justify-end gap-2">
          {order.status === 'draft' && (
            <>
              <button
                className="inline-flex items-center gap-2 rounded border border-blue-200 px-3 py-2 text-blue-600"
                onClick={() => navigate(`/purchase-orders/create?edit=${order.id}`, { state: { purchaseOrder: order } })}
              >
                <FiEdit2 /> Chỉnh sửa
              </button>
              <button className="inline-flex items-center gap-2 rounded border border-red-200 px-3 py-2 text-red-600" onClick={() => openConfirm('delete')}>
                <FiTrash2 /> Xóa
              </button>
              <button className="inline-flex items-center gap-2 rounded bg-primary px-3 py-2 text-white" onClick={() => openConfirm('complete')}>
                <FiCheckCircle /> Hoàn thành
              </button>
            </>
          )}

          {order.status === 'pending' && (
            <>
              <button className="inline-flex items-center gap-2 rounded bg-primary px-3 py-2 text-white" onClick={() => openConfirm('complete')}>
                <FiCheckCircle /> Hoàn thành
              </button>
              <button className="inline-flex items-center gap-2 rounded border border-red-200 px-3 py-2 text-red-600" onClick={() => openConfirm('cancel')}>
                <FiXCircle /> Hủy phiếu
              </button>
            </>
          )}

          {order.status === 'completed' && (
            <>
              <button className="inline-flex items-center gap-2 rounded border border-red-200 px-3 py-2 text-red-600" onClick={() => openConfirm('cancel')}>
                <FiXCircle /> Hủy phiếu
              </button>
              <button className="inline-flex items-center gap-2 rounded border border-primary px-3 py-2 text-primary" onClick={() => window.print()}>
                <FiPrinter /> In phiếu
              </button>
            </>
          )}
        </div>
      </div>

      <ConfirmDialog
        open={confirmState.open}
        onClose={() => setConfirmState({ open: false, action: null, loading: false })}
        onConfirm={handleAction}
        loading={confirmState.loading}
        title={confirmContent.title}
        message={confirmContent.message}
        confirmText={confirmContent.confirmText}
        variant={confirmContent.variant}
      />
    </div>
  );
}
