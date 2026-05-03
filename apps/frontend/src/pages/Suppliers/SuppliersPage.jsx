import { useEffect, useState } from 'react';
import { Formik, Form, Field, ErrorMessage } from 'formik';
import * as Yup from 'yup';
import { FiClock, FiEdit2, FiPlus, FiTrash2 } from 'react-icons/fi';
import { toast } from 'react-toastify';
import { supplierService } from '../../api/services';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import StatusBadge from '../../components/common/StatusBadge';
import { formatCurrency, formatDateOnly } from '../../utils/format';

const schema = Yup.object({
  name: Yup.string().required('Vui lòng nhập tên nhà cung cấp'),
  email: Yup.string().email('Email không hợp lệ').nullable(),
  phone: Yup.string().max(30, 'Số điện thoại không hợp lệ').nullable(),
});

const defaultValues = {
  supplier_code: '',
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  address: '',
  tax_code: '',
  notes: '',
  is_active: true,
};

export default function SuppliersPage() {
  const [suppliers, setSuppliers] = useState([]);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [loading, setLoading] = useState(false);

  const [openModal, setOpenModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);

  const [historyModal, setHistoryModal] = useState({ open: false, supplier: null });
  const [historyData, setHistoryData] = useState([]);
  const [historyMeta, setHistoryMeta] = useState(null);
  const [historyLoading, setHistoryLoading] = useState(false);

  const fetchSuppliers = async (targetPage = page) => {
    setLoading(true);
    try {
      const params = {
        page: targetPage,
        per_page: 10,
        search: search || undefined,
      };

      if (statusFilter !== '') {
        params.is_active = statusFilter === 'true';
      }

      const res = await supplierService.getAll(params);
      setSuppliers(res.data.data || []);
      setMeta(res.data.meta);
      setPage(targetPage);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể tải danh sách nhà cung cấp');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSuppliers(1);
  }, [search, statusFilter]);

  const fetchHistory = async (supplierId, targetPage = 1) => {
    setHistoryLoading(true);
    try {
      const res = await supplierService.getPurchaseHistory(supplierId, { page: targetPage, per_page: 5 });
      setHistoryData(res.data.data || []);
      setHistoryMeta(res.data.meta);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể tải lịch sử nhập hàng');
    } finally {
      setHistoryLoading(false);
    }
  };

  const handleSubmit = async (values, helpers) => {
    try {
      const payload = {
        ...values,
        supplier_code: values.supplier_code || undefined,
      };

      if (editing) {
        await supplierService.update(editing.id, payload);
        toast.success('Cập nhật nhà cung cấp thành công');
      } else {
        await supplierService.create(payload);
        toast.success('Tạo nhà cung cấp thành công');
      }

      setOpenModal(false);
      setEditing(null);
      fetchSuppliers(page);
    } catch (error) {
      const backendErrors = error.response?.data?.errors;
      if (backendErrors) {
        Object.entries(backendErrors).forEach(([field, messages]) => helpers.setFieldError(field, messages[0]));
      }
      toast.error(error.response?.data?.message || 'Lưu nhà cung cấp thất bại');
    } finally {
      helpers.setSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!deleting) return;
    setDeleteLoading(true);
    try {
      await supplierService.delete(deleting.id);
      toast.success('Xóa nhà cung cấp thành công');
      setDeleting(null);
      fetchSuppliers(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Xóa nhà cung cấp thất bại');
    } finally {
      setDeleteLoading(false);
    }
  };

  return (
    <div className="rounded-xl bg-white shadow-card">
      <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
        <h2 className="text-xl font-semibold">Nhà cung cấp</h2>
        <div className="grid gap-2 sm:grid-cols-3">
          <input
            className="rounded-lg border px-3 py-2"
            placeholder="Tìm theo mã, tên, số điện thoại"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <select className="rounded-lg border px-3 py-2" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
            <option value="">Tất cả trạng thái</option>
            <option value="true">Đang hoạt động</option>
            <option value="false">Ngưng hoạt động</option>
          </select>
          <button
            className="flex items-center justify-center gap-2 rounded-lg bg-primary px-3 py-2 text-white"
            onClick={() => {
              setEditing(null);
              setOpenModal(true);
            }}
          >
            <FiPlus /> Thêm NCC
          </button>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 text-left text-slate-600">
            <tr>
              <th className="px-4 py-3">Mã NCC</th>
              <th className="px-4 py-3">Tên NCC</th>
              <th className="px-4 py-3">Người liên hệ</th>
              <th className="px-4 py-3">Điện thoại</th>
              <th className="px-4 py-3">Email</th>
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

            {!loading && suppliers.length === 0 && (
              <tr>
                <td colSpan="7" className="px-4 py-8 text-center text-slate-500">
                  Chưa có nhà cung cấp
                </td>
              </tr>
            )}

            {!loading &&
              suppliers.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-4 py-3 font-medium">{item.supplier_code}</td>
                  <td className="px-4 py-3">{item.name}</td>
                  <td className="px-4 py-3">{item.contact_person || '--'}</td>
                  <td className="px-4 py-3">{item.phone || '--'}</td>
                  <td className="px-4 py-3">{item.email || '--'}</td>
                  <td className="px-4 py-3">
                    <StatusBadge status={item.is_active ? 'active' : 'inactive'} />
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      <button
                        className="rounded border border-emerald-300 p-2 text-emerald-600"
                        title="Lịch sử nhập hàng"
                        onClick={() => {
                          setHistoryModal({ open: true, supplier: item });
                          fetchHistory(item.id, 1);
                        }}
                      >
                        <FiClock />
                      </button>
                      <button
                        className="rounded border p-2 text-blue-600"
                        onClick={() => {
                          setEditing(item);
                          setOpenModal(true);
                        }}
                      >
                        <FiEdit2 />
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

      <Pagination meta={meta} onPageChange={fetchSuppliers} />

      <Modal title={editing ? 'Cập nhật nhà cung cấp' : 'Thêm nhà cung cấp'} open={openModal} onClose={() => setOpenModal(false)} width="max-w-3xl">
        <Formik
          initialValues={{ ...defaultValues, ...editing }}
          validationSchema={schema}
          enableReinitialize
          onSubmit={handleSubmit}
        >
          {({ isSubmitting }) => (
            <Form className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm font-medium">Mã NCC (tự sinh nếu để trống)</label>
                  <Field name="supplier_code" className="w-full rounded-lg border px-3 py-2" />
                  <ErrorMessage name="supplier_code" component="div" className="mt-1 text-xs text-red-600" />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Tên nhà cung cấp *</label>
                  <Field name="name" className="w-full rounded-lg border px-3 py-2" />
                  <ErrorMessage name="name" component="div" className="mt-1 text-xs text-red-600" />
                </div>
              </div>

              <div className="grid gap-4 md:grid-cols-3">
                <div>
                  <label className="mb-1 block text-sm font-medium">Người liên hệ</label>
                  <Field name="contact_person" className="w-full rounded-lg border px-3 py-2" />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Số điện thoại</label>
                  <Field name="phone" className="w-full rounded-lg border px-3 py-2" />
                  <ErrorMessage name="phone" component="div" className="mt-1 text-xs text-red-600" />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Email</label>
                  <Field name="email" className="w-full rounded-lg border px-3 py-2" />
                  <ErrorMessage name="email" component="div" className="mt-1 text-xs text-red-600" />
                </div>
              </div>

              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm font-medium">Địa chỉ</label>
                  <Field name="address" className="w-full rounded-lg border px-3 py-2" />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Mã số thuế</label>
                  <Field name="tax_code" className="w-full rounded-lg border px-3 py-2" />
                </div>
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium">Ghi chú</label>
                <Field as="textarea" name="notes" rows={3} className="w-full rounded-lg border px-3 py-2" />
              </div>

              <label className="flex items-center gap-2 text-sm">
                <Field type="checkbox" name="is_active" className="h-4 w-4" />
                Đang hoạt động
              </label>

              <div className="flex justify-end gap-3">
                <button type="button" className="rounded border px-4 py-2" onClick={() => setOpenModal(false)}>
                  Hủy
                </button>
                <button type="submit" className="rounded bg-primary px-4 py-2 text-white" disabled={isSubmitting}>
                  {isSubmitting ? 'Đang lưu...' : 'Lưu'}
                </button>
              </div>
            </Form>
          )}
        </Formik>
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={handleDelete}
        loading={deleteLoading}
        title="Xóa nhà cung cấp"
        message={`Bạn có chắc muốn xóa nhà cung cấp "${deleting?.name || ''}"?`}
      />

      <Modal
        open={historyModal.open}
        onClose={() => setHistoryModal({ open: false, supplier: null })}
        title={`Lịch sử nhập hàng - ${historyModal.supplier?.name || ''}`}
        width="max-w-4xl"
      >
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-left text-slate-600">
              <tr>
                <th className="px-4 py-3">Mã phiếu</th>
                <th className="px-4 py-3">Kho</th>
                <th className="px-4 py-3">Ngày nhập</th>
                <th className="px-4 py-3">Trạng thái</th>
                <th className="px-4 py-3">Tổng tiền</th>
              </tr>
            </thead>
            <tbody>
              {historyLoading && (
                <tr>
                  <td colSpan="5" className="px-4 py-8 text-center text-slate-500">
                    Đang tải lịch sử...
                  </td>
                </tr>
              )}
              {!historyLoading && historyData.length === 0 && (
                <tr>
                  <td colSpan="5" className="px-4 py-8 text-center text-slate-500">
                    Chưa có lịch sử nhập hàng
                  </td>
                </tr>
              )}
              {!historyLoading &&
                historyData.map((item) => (
                  <tr key={item.id} className="border-t">
                    <td className="px-4 py-3 font-medium">{item.po_code}</td>
                    <td className="px-4 py-3">{item.warehouse?.name || '--'}</td>
                    <td className="px-4 py-3">{formatDateOnly(item.order_date)}</td>
                    <td className="px-4 py-3">
                      <StatusBadge status={item.status} />
                    </td>
                    <td className="px-4 py-3">{formatCurrency(item.total_amount)}</td>
                  </tr>
                ))}
            </tbody>
          </table>
        </div>

        <Pagination
          meta={historyMeta}
          onPageChange={(targetPage) => {
            if (historyModal.supplier) {
              fetchHistory(historyModal.supplier.id, targetPage);
            }
          }}
        />
      </Modal>
    </div>
  );
}
