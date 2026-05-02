import { useEffect, useState } from 'react';
import { FiEye, FiPlus, FiTrash2 } from 'react-icons/fi';
import { customersApi, ordersApi, productsApi } from '../api/services';
import { formatCurrency, formatDate } from '../utils/format';
import Modal from '../components/common/Modal';
import OrderForm from '../components/forms/OrderForm';
import Pagination from '../components/common/Pagination';
import ConfirmDialog from '../components/common/ConfirmDialog';
import { ORDER_STATUSES, PAYMENT_STATUSES } from '../utils/constants';
import { toast } from 'react-toastify';

export default function OrdersPage() {
  const [orders, setOrders] = useState([]);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [orderStatus, setOrderStatus] = useState('');
  const [paymentStatus, setPaymentStatus] = useState('');
  const [openCreate, setOpenCreate] = useState(false);
  const [customers, setCustomers] = useState([]);
  const [products, setProducts] = useState([]);
  const [detailOrder, setDetailOrder] = useState(null);
  const [deleting, setDeleting] = useState(null);

  const loadFiltersData = async () => {
    const [cRes, pRes] = await Promise.all([
      customersApi.list({ per_page: 100 }),
      productsApi.list({ per_page: 100 }),
    ]);
    setCustomers(cRes.data.data || []);
    setProducts(pRes.data.data || []);
  };

  const loadOrders = async (targetPage = page) => {
    try {
      const res = await ordersApi.list({
        page: targetPage,
        per_page: 10,
        search,
        order_status: orderStatus || undefined,
        payment_status: paymentStatus || undefined,
      });
      setOrders(res.data.data || []);
      setMeta(res.data.meta);
      setPage(targetPage);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không tải được đơn hàng');
    }
  };

  useEffect(() => {
    loadFiltersData();
  }, []);

  useEffect(() => {
    loadOrders(1);
  }, [search, orderStatus, paymentStatus]);

  const handleCreateOrder = async (values, helpers) => {
    try {
      const payload = {
        ...values,
        customer_id: values.customer_id || null,
        discount: Number(values.discount || 0),
        items: values.items.map((item) => ({
          product_id: Number(item.product_id),
          quantity: Number(item.quantity),
          unit_price: Number(item.unit_price),
        })),
      };
      await ordersApi.create(payload);
      toast.success('Tạo đơn hàng thành công');
      setOpenCreate(false);
      loadOrders(page);
    } catch (error) {
      const backendError = error.response?.data?.errors;
      if (backendError) {
        Object.entries(backendError).forEach(([field, messages]) => helpers.setFieldError(field, messages[0]));
      }
      toast.error(error.response?.data?.message || 'Tạo đơn hàng thất bại');
    } finally {
      helpers.setSubmitting(false);
    }
  };

  const openDetail = async (id) => {
    try {
      const res = await ordersApi.show(id);
      setDetailOrder(res.data.data);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không lấy được chi tiết đơn hàng');
    }
  };

  const handleDelete = async () => {
    try {
      await ordersApi.remove(deleting.id);
      toast.success('Xóa đơn hàng thành công');
      setDeleting(null);
      loadOrders(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Xóa thất bại');
    }
  };

  return (
    <div className="space-y-4">
      <div className="rounded-xl bg-white p-4 shadow-card">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <h2 className="text-xl font-semibold">Quản lý đơn hàng</h2>
          <div className="flex flex-wrap gap-2">
            <input
              className="rounded-lg border px-3 py-2"
              placeholder="Tìm theo mã đơn"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
            <select className="rounded-lg border px-3 py-2" value={orderStatus} onChange={(e) => setOrderStatus(e.target.value)}>
              <option value="">Tất cả trạng thái đơn</option>
              {ORDER_STATUSES.map((x) => (
                <option key={x.value} value={x.value}>
                  {x.label}
                </option>
              ))}
            </select>
            <select className="rounded-lg border px-3 py-2" value={paymentStatus} onChange={(e) => setPaymentStatus(e.target.value)}>
              <option value="">Tất cả thanh toán</option>
              {PAYMENT_STATUSES.map((x) => (
                <option key={x.value} value={x.value}>
                  {x.label}
                </option>
              ))}
            </select>
            <button className="flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white" onClick={() => setOpenCreate(true)}>
              <FiPlus /> Tạo đơn
            </button>
          </div>
        </div>
      </div>

      <div className="rounded-xl bg-white shadow-card">
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-left text-slate-600">
              <tr>
                <th className="px-4 py-3">Mã đơn</th>
                <th className="px-4 py-3">Khách hàng</th>
                <th className="px-4 py-3">Ngày</th>
                <th className="px-4 py-3">Thành tiền</th>
                <th className="px-4 py-3">Trạng thái</th>
                <th className="px-4 py-3">Thanh toán</th>
                <th className="px-4 py-3">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              {orders.length === 0 && (
                <tr>
                  <td colSpan="7" className="px-4 py-8 text-center text-slate-500">
                    Không có dữ liệu
                  </td>
                </tr>
              )}
              {orders.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-4 py-3">{item.order_code}</td>
                  <td className="px-4 py-3">{item.customer?.name || 'Khách lẻ'}</td>
                  <td className="px-4 py-3">{formatDate(item.order_date)}</td>
                  <td className="px-4 py-3">{formatCurrency(item.final_amount)}</td>
                  <td className="px-4 py-3">{item.order_status}</td>
                  <td className="px-4 py-3">{item.payment_status}</td>
                  <td className="px-4 py-3">
                    <div className="flex gap-2">
                      <button className="rounded border p-2 text-primary" onClick={() => openDetail(item.id)}>
                        <FiEye />
                      </button>
                      <button className="rounded border p-2 text-red-600" onClick={() => setDeleting(item)}>
                        <FiTrash2 />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <Pagination meta={meta} onPageChange={loadOrders} />
      </div>

      <Modal title="Tạo đơn hàng" open={openCreate} onClose={() => setOpenCreate(false)}>
        <OrderForm customers={customers} products={products} onSubmit={handleCreateOrder} onCancel={() => setOpenCreate(false)} />
      </Modal>

      <Modal title="Chi tiết đơn hàng" open={Boolean(detailOrder)} onClose={() => setDetailOrder(null)}>
        {detailOrder && (
          <div className="space-y-3 text-sm">
            <div className="grid gap-3 md:grid-cols-2">
              <p>
                <b>Mã đơn:</b> {detailOrder.order_code}
              </p>
              <p>
                <b>Khách hàng:</b> {detailOrder.customer?.name || 'Khách lẻ'}
              </p>
              <p>
                <b>Ngày:</b> {formatDate(detailOrder.order_date)}
              </p>
              <p>
                <b>Nhân viên:</b> {detailOrder.staff?.name || '--'}
              </p>
            </div>
            <div className="overflow-x-auto rounded border">
              <table className="min-w-full text-sm">
                <thead className="bg-slate-50">
                  <tr>
                    <th className="px-3 py-2 text-left">Sản phẩm</th>
                    <th className="px-3 py-2 text-right">SL</th>
                    <th className="px-3 py-2 text-right">Đơn giá</th>
                    <th className="px-3 py-2 text-right">Thành tiền</th>
                  </tr>
                </thead>
                <tbody>
                  {detailOrder.order_items?.map((item) => (
                    <tr key={item.id} className="border-t">
                      <td className="px-3 py-2">{item.product?.name}</td>
                      <td className="px-3 py-2 text-right">{item.quantity}</td>
                      <td className="px-3 py-2 text-right">{formatCurrency(item.unit_price)}</td>
                      <td className="px-3 py-2 text-right">{formatCurrency(item.total_price)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="text-right">
              <p>Tổng: {formatCurrency(detailOrder.total_amount)}</p>
              <p>Giảm giá: {formatCurrency(detailOrder.discount)}</p>
              <p className="text-base font-semibold">Thành tiền: {formatCurrency(detailOrder.final_amount)}</p>
            </div>
          </div>
        )}
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={handleDelete}
        title="Xóa đơn hàng"
        message={`Bạn có chắc muốn xóa đơn "${deleting?.order_code || ''}"?`}
      />
    </div>
  );
}
