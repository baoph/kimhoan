import { useEffect, useMemo, useState } from 'react';
import { FiArrowLeft, FiPlus, FiTrash2 } from 'react-icons/fi';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import { purchaseOrderService } from '../../api/services';
import ProductSelect from '../../components/common/ProductSelect';
import SupplierSelect from '../../components/common/SupplierSelect';
import { useWarehouse } from '../../contexts/WarehouseContext';
import { formatCurrency, formatNumber } from '../../utils/format';

const newRow = () => ({
  product_id: '',
  product: null,
  quantity: 1,
  unit_price: 0,
});

export default function CreatePurchaseOrderPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const { currentWarehouse } = useWarehouse();

  const editId = searchParams.get('edit');
  const initialOrderFromState = location.state?.purchaseOrder;

  const [form, setForm] = useState({
    supplier_id: '',
    order_date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [newRow()],
  });
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});

  const isEditMode = Boolean(editId || initialOrderFromState?.id);

  const grandTotal = useMemo(
    () => form.items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0),
    [form.items]
  );

  const hydrateOrderToForm = (order) => {
    setForm({
      supplier_id: String(order.supplier_id || order.supplier?.id || ''),
      order_date: order.order_date ? String(order.order_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
      notes: order.notes || '',
      items: (order.items || []).map((item) => ({
        product_id: String(item.product_id || item.product?.id || ''),
        product: item.product || null,
        quantity: Number(item.quantity || 1),
        unit_price: Number(item.unit_price || 0),
      })),
    });
  };

  useEffect(() => {
    if (!isEditMode) return;

    const loadOrder = async () => {
      setLoading(true);
      try {
        if (initialOrderFromState) {
          hydrateOrderToForm(initialOrderFromState);
          return;
        }

        const res = await purchaseOrderService.getById(editId);
        const order = res.data.data;
        if (!['draft', 'pending'].includes(order.status)) {
          toast.error('Chỉ có thể chỉnh sửa phiếu nhập draft/pending');
          navigate(`/purchase-orders/${order.id}`);
          return;
        }
        hydrateOrderToForm(order);
      } catch (error) {
        toast.error(error.response?.data?.message || 'Không thể tải phiếu nhập để chỉnh sửa');
        navigate('/purchase-orders');
      } finally {
        setLoading(false);
      }
    };

    loadOrder();
  }, [editId, initialOrderFromState, isEditMode, navigate]);

  const validate = () => {
    const nextErrors = {};

    if (!form.supplier_id) nextErrors.supplier_id = 'Vui lòng chọn nhà cung cấp';
    if (!form.order_date) nextErrors.order_date = 'Vui lòng chọn ngày nhập';

    if (!form.items.length) {
      nextErrors.items = 'Cần ít nhất 1 sản phẩm';
    } else {
      const rowErrors = [];
      form.items.forEach((item, index) => {
        const rowError = {};
        if (!item.product_id) rowError.product_id = 'Chọn sản phẩm';
        if (!Number(item.quantity) || Number(item.quantity) < 1) rowError.quantity = 'Số lượng >= 1';
        if (Number(item.unit_price) < 0) rowError.unit_price = 'Đơn giá >= 0';
        if (Object.keys(rowError).length) {
          rowErrors[index] = rowError;
        }
      });
      if (rowErrors.length) nextErrors.rowErrors = rowErrors;
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const buildPayload = (status) => ({
    supplier_id: Number(form.supplier_id),
    order_date: form.order_date,
    notes: form.notes || null,
    status,
    items: form.items.map((item) => ({
      product_id: Number(item.product_id),
      quantity: Number(item.quantity),
      unit_price: Number(item.unit_price),
    })),
  });

  const handleSave = async (targetStatus) => {
    if (!currentWarehouse?.id) {
      toast.error('Không tìm thấy kho hiện tại. Vui lòng chọn kho ở thanh trên cùng.');
      return;
    }

    if (!validate()) return;

    setSaving(true);
    try {
      const payload = buildPayload(targetStatus === 'complete' ? 'pending' : 'draft');
      let po;

      if (isEditMode) {
        const id = editId || initialOrderFromState?.id;
        const res = await purchaseOrderService.update(id, payload);
        po = res.data.data;
      } else {
        const res = await purchaseOrderService.create(payload);
        po = res.data.data;
      }

      if (targetStatus === 'complete') {
        await purchaseOrderService.complete(po.id);
        toast.success('Lưu và hoàn thành phiếu nhập thành công');
      } else {
        toast.success('Lưu phiếu nhập nháp thành công');
      }

      navigate('/purchase-orders');
    } catch (error) {
      const backendErrors = error.response?.data?.errors;
      if (backendErrors) {
        const rowErrors = [];
        Object.entries(backendErrors).forEach(([field, messages]) => {
          const matched = field.match(/^items\.(\d+)\.(.+)$/);
          if (matched) {
            const index = Number(matched[1]);
            const key = matched[2];
            rowErrors[index] = {
              ...(rowErrors[index] || {}),
              [key]: messages[0],
            };
          } else {
            setErrors((prev) => ({ ...prev, [field]: messages[0] }));
          }
        });

        if (rowErrors.length) {
          setErrors((prev) => ({ ...prev, rowErrors }));
        }
      }
      toast.error(error.response?.data?.message || 'Không thể lưu phiếu nhập');
    } finally {
      setSaving(false);
    }
  };

  const updateRow = (index, patch) => {
    setForm((prev) => ({
      ...prev,
      items: prev.items.map((item, itemIndex) => (itemIndex === index ? { ...item, ...patch } : item)),
    }));
  };

  if (loading) {
    return <div className="rounded-xl bg-white p-6 text-center text-slate-500 shadow-card">Đang tải dữ liệu phiếu nhập...</div>;
  }

  return (
    <div className="space-y-4">
      <div className="rounded-xl bg-white p-4 shadow-card">
        <button className="mb-3 inline-flex items-center gap-2 text-sm text-primary" onClick={() => navigate('/purchase-orders')}>
          <FiArrowLeft /> Quay lại danh sách phiếu nhập
        </button>
        <h2 className="text-xl font-semibold">{isEditMode ? 'Chỉnh sửa phiếu nhập' : 'Tạo phiếu nhập mới'}</h2>
        <p className="mt-1 text-sm text-slate-500">Kho hiện tại: {currentWarehouse?.name || 'Chưa chọn kho'}</p>
      </div>

      <div className="rounded-xl bg-white p-4 shadow-card">
        <div className="grid gap-4 md:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm font-medium">Nhà cung cấp *</label>
            <SupplierSelect value={form.supplier_id} onChange={(value) => setForm((prev) => ({ ...prev, supplier_id: value }))} />
            {errors.supplier_id && <p className="mt-1 text-xs text-red-600">{errors.supplier_id}</p>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">Ngày nhập *</label>
            <input
              type="date"
              className="w-full rounded-lg border px-3 py-2"
              value={form.order_date}
              onChange={(e) => setForm((prev) => ({ ...prev, order_date: e.target.value }))}
            />
            {errors.order_date && <p className="mt-1 text-xs text-red-600">{errors.order_date}</p>}
          </div>

          <div className="md:col-span-2">
            <label className="mb-1 block text-sm font-medium">Ghi chú</label>
            <textarea
              rows={2}
              className="w-full rounded-lg border px-3 py-2"
              value={form.notes}
              onChange={(e) => setForm((prev) => ({ ...prev, notes: e.target.value }))}
            />
          </div>
        </div>
      </div>

      <div className="rounded-xl bg-white p-4 shadow-card">
        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
          <h3 className="text-lg font-semibold">Danh sách sản phẩm</h3>
          <button
            className="inline-flex items-center gap-2 rounded-lg border border-primary px-3 py-2 text-primary"
            onClick={() => setForm((prev) => ({ ...prev, items: [...prev.items, newRow()] }))}
          >
            <FiPlus /> Thêm dòng
          </button>
        </div>

        {errors.items && <p className="mb-2 text-sm text-red-600">{errors.items}</p>}

        <div className="space-y-3">
          {form.items.map((item, index) => {
            const rowError = errors.rowErrors?.[index] || {};
            const rowTotal = Number(item.quantity || 0) * Number(item.unit_price || 0);

            return (
              <div key={index} className="rounded-lg border p-3">
                <div className="grid gap-3 lg:grid-cols-12">
                  <div className="lg:col-span-5">
                    <label className="mb-1 block text-sm font-medium">Sản phẩm *</label>
                    <ProductSelect
                      value={item.product}
                      onChange={(product) => {
                        if (!product) {
                          updateRow(index, { product: null, product_id: '', unit_price: 0 });
                          return;
                        }
                        updateRow(index, {
                          product,
                          product_id: String(product.id),
                          unit_price: Number(product.cost_price || 0),
                        });
                      }}
                    />
                    {rowError.product_id && <p className="mt-1 text-xs text-red-600">{rowError.product_id}</p>}
                  </div>

                  <div className="lg:col-span-2">
                    <label className="mb-1 block text-sm font-medium">Số lượng *</label>
                    <input
                      type="number"
                      min={1}
                      className="w-full rounded-lg border px-3 py-2"
                      value={item.quantity}
                      onChange={(e) => updateRow(index, { quantity: e.target.value })}
                    />
                    {rowError.quantity && <p className="mt-1 text-xs text-red-600">{rowError.quantity}</p>}
                  </div>

                  <div className="lg:col-span-2">
                    <label className="mb-1 block text-sm font-medium">Đơn giá *</label>
                    <input
                      type="number"
                      min={0}
                      className="w-full rounded-lg border px-3 py-2"
                      value={item.unit_price}
                      onChange={(e) => updateRow(index, { unit_price: e.target.value })}
                    />
                    {rowError.unit_price && <p className="mt-1 text-xs text-red-600">{rowError.unit_price}</p>}
                  </div>

                  <div className="lg:col-span-2">
                    <label className="mb-1 block text-sm font-medium">Thành tiền</label>
                    <div className="rounded-lg border bg-slate-50 px-3 py-2">{formatCurrency(rowTotal)}</div>
                  </div>

                  <div className="lg:col-span-1">
                    <label className="mb-1 block text-sm font-medium">&nbsp;</label>
                    <button
                      className="w-full rounded-lg border border-red-200 px-2 py-2 text-red-600"
                      onClick={() => {
                        if (form.items.length === 1) return;
                        setForm((prev) => ({
                          ...prev,
                          items: prev.items.filter((_, itemIndex) => itemIndex !== index),
                        }));
                      }}
                    >
                      <FiTrash2 className="mx-auto" />
                    </button>
                  </div>
                </div>

                {item.product && (
                  <p className="mt-2 text-xs text-slate-500">
                    Mã: {item.product.product_code} • Tồn hiện tại: {formatNumber(item.product.stock_quantity || 0)}
                  </p>
                )}
              </div>
            );
          })}
        </div>

        <div className="mt-4 rounded-lg bg-blue-50 p-4 text-blue-900">
          <p className="text-sm">Tổng cộng: {formatCurrency(grandTotal)}</p>
        </div>

        <div className="mt-4 flex flex-wrap justify-end gap-3">
          <button className="rounded border px-4 py-2" onClick={() => navigate('/purchase-orders')}>
            Hủy
          </button>
          <button className="rounded border border-primary px-4 py-2 text-primary" disabled={saving} onClick={() => handleSave('draft')}>
            {saving ? 'Đang xử lý...' : 'Lưu nháp'}
          </button>
          <button className="rounded bg-primary px-4 py-2 text-white" disabled={saving} onClick={() => handleSave('complete')}>
            {saving ? 'Đang xử lý...' : 'Lưu & hoàn thành'}
          </button>
        </div>
      </div>
    </div>
  );
}
