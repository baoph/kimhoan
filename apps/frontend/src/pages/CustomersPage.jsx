import { useEffect, useState } from 'react';
import { FiEdit2, FiPlus, FiTrash2 } from 'react-icons/fi';
import { customersApi } from '../api/services';
import Modal from '../components/common/Modal';
import CustomerForm from '../components/forms/CustomerForm';
import Pagination from '../components/common/Pagination';
import ConfirmDialog from '../components/common/ConfirmDialog';
import { toast } from 'react-toastify';

export default function CustomersPage() {
  const [customers, setCustomers] = useState([]);
  const [meta, setMeta] = useState(null);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [openModal, setOpenModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);

  const loadCustomers = async (targetPage = page) => {
    try {
      const res = await customersApi.list({ page: targetPage, per_page: 10, search });
      setCustomers(res.data.data || []);
      setMeta(res.data.meta);
      setPage(targetPage);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không tải được khách hàng');
    }
  };

  useEffect(() => {
    loadCustomers(1);
  }, [search]);

  const handleSubmit = async (values, helpers) => {
    try {
      if (editing) {
        await customersApi.update(editing.id, values);
        toast.success('Cập nhật khách hàng thành công');
      } else {
        await customersApi.create(values);
        toast.success('Tạo khách hàng thành công');
      }
      setOpenModal(false);
      setEditing(null);
      loadCustomers(page);
    } catch (error) {
      const backendError = error.response?.data?.errors;
      if (backendError) {
        Object.entries(backendError).forEach(([field, messages]) => helpers.setFieldError(field, messages[0]));
      }
      toast.error(error.response?.data?.message || 'Lưu khách hàng thất bại');
    } finally {
      helpers.setSubmitting(false);
    }
  };

  const handleDelete = async () => {
    try {
      await customersApi.remove(deleting.id);
      toast.success('Xóa khách hàng thành công');
      setDeleting(null);
      loadCustomers(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Xóa thất bại');
    }
  };

  return (
    <div className="rounded-xl bg-white shadow-card">
      <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
        <h2 className="text-xl font-semibold">Quản lý khách hàng</h2>
        <div className="flex gap-2">
          <input
            className="rounded-lg border px-3 py-2"
            placeholder="Tìm theo mã, tên, số điện thoại"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <button
            className="flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white"
            onClick={() => {
              setEditing(null);
              setOpenModal(true);
            }}
          >
            <FiPlus /> Thêm mới
          </button>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 text-left text-slate-600">
            <tr>
              <th className="px-4 py-3">Mã KH</th>
              <th className="px-4 py-3">Tên khách hàng</th>
              <th className="px-4 py-3">Điện thoại</th>
              <th className="px-4 py-3">Email</th>
              <th className="px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {customers.length === 0 && (
              <tr>
                <td colSpan="5" className="px-4 py-8 text-center text-slate-500">
                  Không có dữ liệu
                </td>
              </tr>
            )}
            {customers.map((item) => (
              <tr key={item.id} className="border-t">
                <td className="px-4 py-3">{item.customer_code}</td>
                <td className="px-4 py-3">{item.name}</td>
                <td className="px-4 py-3">{item.phone1 || '--'}</td>
                <td className="px-4 py-3">{item.email || '--'}</td>
                <td className="px-4 py-3">
                  <div className="flex gap-2">
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

      <Pagination meta={meta} onPageChange={loadCustomers} />

      <Modal title={editing ? 'Sửa khách hàng' : 'Thêm khách hàng'} open={openModal} onClose={() => setOpenModal(false)}>
        <CustomerForm initialData={editing} onSubmit={handleSubmit} onCancel={() => setOpenModal(false)} />
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={handleDelete}
        title="Xóa khách hàng"
        message={`Bạn có chắc muốn xóa khách hàng "${deleting?.name || ''}"?`}
      />
    </div>
  );
}
