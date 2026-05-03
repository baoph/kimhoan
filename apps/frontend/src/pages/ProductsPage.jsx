import { useEffect, useState } from 'react';
import { FiEdit2, FiPlus, FiTrash2 } from 'react-icons/fi';
import { brandsApi, categoriesApi, productsApi } from '../api/services';
import { formatCurrency, formatNumber } from '../utils/format';
import Modal from '../components/common/Modal';
import ProductForm from '../components/forms/ProductForm';
import Pagination from '../components/common/Pagination';
import ConfirmDialog from '../components/common/ConfirmDialog';
import { toast } from 'react-toastify';
import { useWarehouse } from '../contexts/WarehouseContext';

export default function ProductsPage() {
  const { currentWarehouse } = useWarehouse();
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [brands, setBrands] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState(null);
  const [openModal, setOpenModal] = useState(false);
  const [deleting, setDeleting] = useState(null);

  const loadMeta = async () => {
    const [categoriesRes, brandsRes] = await Promise.all([
      categoriesApi.list({ per_page: 100 }),
      brandsApi.list({ per_page: 100 }),
    ]);
    setCategories(categoriesRes.data.data || []);
    setBrands(brandsRes.data.data || []);
  };

  const loadProducts = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await productsApi.list({ page: targetPage, per_page: 10, search });
      setProducts(res.data.data || []);
      setMeta(res.data.meta);
      setPage(targetPage);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Lỗi tải danh sách sản phẩm');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadMeta();
  }, []);

  useEffect(() => {
    loadProducts(1);
  }, [search]);

  const handleSubmit = async (values, helpers) => {
    const payload = {
      ...values,
      category_id: values.category_id || null,
      brand_id: values.brand_id || null,
      images: [],
    };

    try {
      if (editing) {
        await productsApi.update(editing.id, payload);
        toast.success('Cập nhật sản phẩm thành công');
      } else {
        await productsApi.create(payload);
        toast.success('Tạo sản phẩm thành công');
      }
      setOpenModal(false);
      setEditing(null);
      loadProducts(page);
    } catch (error) {
      const backendError = error.response?.data?.errors;
      if (backendError) {
        Object.entries(backendError).forEach(([field, messages]) => helpers.setFieldError(field, messages[0]));
      }
      toast.error(error.response?.data?.message || 'Lưu sản phẩm thất bại');
    } finally {
      helpers.setSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!deleting) return;
    try {
      await productsApi.remove(deleting.id);
      toast.success('Xóa sản phẩm thành công');
      setDeleting(null);
      loadProducts(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Xóa thất bại');
    }
  };

  return (
    <div className="rounded-xl bg-white shadow-card">
      <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="text-xl font-semibold">Quản lý hàng hóa</h2>
          <p className="mt-1 text-sm text-slate-500">Kho hiện tại: {currentWarehouse?.name || 'Chưa chọn kho'}</p>
        </div>
        <div className="flex gap-2">
          <input
            className="rounded-lg border px-3 py-2"
            placeholder="Tìm theo mã, tên, mã vạch"
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
              <th className="px-4 py-3">Mã</th>
              <th className="px-4 py-3">Tên hàng</th>
              <th className="px-4 py-3">Giá bán</th>
              <th className="px-4 py-3">Giá vốn</th>
              <th className="px-4 py-3">Tồn kho</th>
              <th className="px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {!loading && products.length === 0 && (
              <tr>
                <td colSpan="6" className="px-4 py-8 text-center text-slate-500">
                  Không có dữ liệu
                </td>
              </tr>
            )}
            {products.map((item) => (
              <tr key={item.id} className="border-t">
                <td className="px-4 py-3">{item.product_code}</td>
                <td className="px-4 py-3">{item.name}</td>
                <td className="px-4 py-3">{formatCurrency(item.selling_price)}</td>
                <td className="px-4 py-3">{formatCurrency(item.cost_price)}</td>
                <td className="px-4 py-3">{formatNumber(item.stock_quantity)}</td>
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

      <Pagination meta={meta} onPageChange={loadProducts} />

      <Modal title={editing ? 'Sửa hàng hóa' : 'Thêm hàng hóa'} open={openModal} onClose={() => setOpenModal(false)}>
        <ProductForm
          initialData={editing}
          categories={categories}
          brands={brands}
          onSubmit={handleSubmit}
          onCancel={() => setOpenModal(false)}
        />
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={handleDelete}
        title="Xóa sản phẩm"
        message={`Bạn có chắc muốn xóa sản phẩm "${deleting?.name || ''}"?`}
      />
    </div>
  );
}
