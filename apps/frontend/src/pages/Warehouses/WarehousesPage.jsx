import { useEffect, useState } from 'react';
import { Formik, Form, Field, ErrorMessage } from 'formik';
import * as Yup from 'yup';
import { FiEdit2, FiEye, FiPlus, FiTrash2 } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { warehouseService } from '../../api/services';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import StatusBadge from '../../components/common/StatusBadge';

const schema = Yup.object({
  code: Yup.string().required('Vui lòng nhập mã kho'),
  name: Yup.string().required('Vui lòng nhập tên kho'),
  phone: Yup.string().max(30, 'Số điện thoại không hợp lệ').nullable(),
});

const defaultValues = {
  code: '',
  name: '',
  address: '',
  phone: '',
  manager_name: '',
  is_active: true,
};

export default function WarehousesPage() {
  const navigate = useNavigate();
  const [warehouses, setWarehouses] = useState([]);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [loading, setLoading] = useState(false);
  const [openModal, setOpenModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);

  const fetchWarehouses = async (targetPage = page) => {
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

      const res = await warehouseService.getAll(params);
      setWarehouses(res.data.data || []);
      setMeta(res.data.meta);
      setPage(targetPage);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể tải danh sách kho');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchWarehouses(1);
  }, [search, statusFilter]);

  const handleSubmit = async (values, helpers) => {
    try {
      const payload = {
        ...values,
        is_active: Boolean(values.is_active),
      };

      if (editing) {
        await warehouseService.update(editing.id, payload);
        toast.success('Cập nhật kho thành công');
      } else {
        await warehouseService.create(payload);
        toast.success('Tạo kho thành công');
      }

      setOpenModal(false);
      setEditing(null);
      fetchWarehouses(page);
    } catch (error) {
      const backendErrors = error.response?.data?.errors;
      if (backendErrors) {
        Object.entries(backendErrors).forEach(([field, messages]) => helpers.setFieldError(field, messages[0]));
      }
      toast.error(error.response?.data?.message || 'Lưu kho thất bại');
    } finally {
      helpers.setSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!deleting) return;
    setDeleteLoading(true);
    try {
      await warehouseService.delete(deleting.id);
      toast.success('Xóa kho thành công');
      setDeleting(null);
      fetchWarehouses(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Xóa kho thất bại');
    } finally {
      setDeleteLoading(false);
    }
  };

  return (
    <div className="rounded-xl bg-white shadow-card">
      <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
        <h2 className="text-xl font-semibold">Danh sách kho</h2>
        <div className="grid gap-2 sm:grid-cols-3">
          <input
            className="rounded-lg border px-3 py-2"
            placeholder="Tìm theo mã hoặc tên kho"
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
            <FiPlus /> Thêm kho
          </button>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 text-left text-slate-600">
            <tr>
              <th className="px-4 py-3">Mã kho</th>
              <th className="px-4 py-3">Tên kho</th>
              <th className="px-4 py-3">Địa chỉ</th>
              <th className="px-4 py-3">Số điện thoại</th>
              <th className="px-4 py-3">Quản lý</th>
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

            {!loading && warehouses.length === 0 && (
              <tr>
                <td colSpan="7" className="px-4 py-8 text-center text-slate-500">
                  Chưa có kho nào
                </td>
              </tr>
            )}

            {!loading &&
              warehouses.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-4 py-3 font-medium">{item.code}</td>
                  <td className="px-4 py-3">{item.name}</td>
                  <td className="px-4 py-3">{item.address || '--'}</td>
                  <td className="px-4 py-3">{item.phone || '--'}</td>
                  <td className="px-4 py-3">{item.manager_name || '--'}</td>
                  <td className="px-4 py-3">
                    <StatusBadge status={item.is_active ? 'active' : 'inactive'} />
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      <button
                        className="rounded border border-blue-200 p-2 text-blue-600"
                        onClick={() => navigate(`/warehouses/${item.id}/stock`, { state: { warehouse: item } })}
                        title="Xem tồn kho"
                      >
                        <FiEye />
                      </button>
                      <button
                        className="rounded border p-2 text-blue-600"
                        onClick={() => {
                          setEditing(item);
                          setOpenModal(true);
                        }}
                        title="Chỉnh sửa"
                      >
                        <FiEdit2 />
                      </button>
                      <button className="rounded border p-2 text-red-600" onClick={() => setDeleting(item)} title="Xóa">
                        <FiTrash2 />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      <Pagination meta={meta} onPageChange={fetchWarehouses} />

      <Modal title={editing ? 'Cập nhật kho' : 'Thêm kho mới'} open={openModal} onClose={() => setOpenModal(false)} width="max-w-2xl">
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
                  <label className="mb-1 block text-sm font-medium">Mã kho *</label>
                  <Field name="code" className="w-full rounded-lg border px-3 py-2" />
                  <ErrorMessage name="code" component="div" className="mt-1 text-xs text-red-600" />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Tên kho *</label>
                  <Field name="name" className="w-full rounded-lg border px-3 py-2" />
                  <ErrorMessage name="name" component="div" className="mt-1 text-xs text-red-600" />
                </div>
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium">Địa chỉ</label>
                <Field name="address" className="w-full rounded-lg border px-3 py-2" />
              </div>

              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm font-medium">Số điện thoại</label>
                  <Field name="phone" className="w-full rounded-lg border px-3 py-2" />
                  <ErrorMessage name="phone" component="div" className="mt-1 text-xs text-red-600" />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Người quản lý</label>
                  <Field name="manager_name" className="w-full rounded-lg border px-3 py-2" />
                </div>
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
        title="Xóa kho"
        message={`Bạn có chắc muốn xóa kho "${deleting?.name || ''}"?`}
      />
    </div>
  );
}
