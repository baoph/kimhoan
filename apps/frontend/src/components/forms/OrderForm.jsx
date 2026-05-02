import { Formik, Form, Field, FieldArray, ErrorMessage } from 'formik';
import * as Yup from 'yup';
import { ORDER_STATUSES, PAYMENT_STATUSES } from '../../utils/constants';
import { formatCurrency } from '../../utils/format';

const schema = Yup.object({
  order_code: Yup.string().required('Bắt buộc'),
  order_date: Yup.string().required('Bắt buộc'),
  items: Yup.array()
    .of(
      Yup.object({
        product_id: Yup.number().required('Bắt buộc'),
        quantity: Yup.number().min(1, '>=1').required('Bắt buộc'),
        unit_price: Yup.number().min(0, '>=0').required('Bắt buộc'),
      })
    )
    .min(1, 'Cần ít nhất 1 sản phẩm'),
});

const defaults = {
  order_code: `DH${Date.now()}`,
  customer_id: '',
  order_date: new Date().toISOString().slice(0, 16),
  discount: 0,
  payment_status: 'pending',
  order_status: 'confirmed',
  notes: '',
  items: [{ product_id: '', quantity: 1, unit_price: 0 }],
};

export default function OrderForm({ customers, products, onSubmit, onCancel }) {
  return (
    <Formik initialValues={defaults} validationSchema={schema} onSubmit={onSubmit}>
      {({ values, isSubmitting, setFieldValue }) => {
        const total = values.items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0);
        const finalAmount = Math.max(total - Number(values.discount || 0), 0);

        return (
          <Form className="space-y-4">
            <div className="grid gap-4 md:grid-cols-3">
              <div>
                <label className="mb-1 block text-sm font-medium">Mã đơn</label>
                <Field name="order_code" className="w-full rounded-lg border px-3 py-2" />
                <ErrorMessage name="order_code" component="div" className="mt-1 text-xs text-red-600" />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Khách hàng</label>
                <Field as="select" name="customer_id" className="w-full rounded-lg border px-3 py-2">
                  <option value="">Khách lẻ</option>
                  {customers.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.customer_code} - {c.name}
                    </option>
                  ))}
                </Field>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Ngày đặt</label>
                <Field type="datetime-local" name="order_date" className="w-full rounded-lg border px-3 py-2" />
              </div>
            </div>

            <FieldArray name="items">
              {({ push, remove }) => (
                <div className="space-y-2 rounded-lg border p-3">
                  <div className="mb-2 flex items-center justify-between">
                    <h4 className="font-medium">Sản phẩm trong đơn</h4>
                    <button
                      type="button"
                      className="rounded border border-primary px-3 py-1 text-sm text-primary"
                      onClick={() => push({ product_id: '', quantity: 1, unit_price: 0 })}
                    >
                      + Thêm sản phẩm
                    </button>
                  </div>

                  {values.items.map((_, index) => (
                    <div key={index} className="grid gap-2 md:grid-cols-12">
                      <div className="md:col-span-6">
                        <Field
                          as="select"
                          name={`items.${index}.product_id`}
                          className="w-full rounded-lg border px-3 py-2"
                          onChange={(e) => {
                            const productId = Number(e.target.value);
                            const product = products.find((p) => p.id === productId);
                            setFieldValue(`items.${index}.product_id`, productId || '');
                            setFieldValue(`items.${index}.unit_price`, Number(product?.selling_price || 0));
                          }}
                        >
                          <option value="">Chọn sản phẩm</option>
                          {products.map((p) => (
                            <option key={p.id} value={p.id}>
                              {p.product_code} - {p.name}
                            </option>
                          ))}
                        </Field>
                      </div>
                      <div className="md:col-span-2">
                        <Field type="number" name={`items.${index}.quantity`} className="w-full rounded-lg border px-3 py-2" />
                      </div>
                      <div className="md:col-span-3">
                        <Field type="number" name={`items.${index}.unit_price`} className="w-full rounded-lg border px-3 py-2" />
                      </div>
                      <div className="md:col-span-1">
                        <button
                          type="button"
                          className="w-full rounded bg-red-50 px-2 py-2 text-red-600"
                          onClick={() => values.items.length > 1 && remove(index)}
                        >
                          X
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </FieldArray>

            <div className="grid gap-4 md:grid-cols-3">
              <div>
                <label className="mb-1 block text-sm font-medium">Giảm giá</label>
                <Field type="number" name="discount" className="w-full rounded-lg border px-3 py-2" />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Trạng thái đơn</label>
                <Field as="select" name="order_status" className="w-full rounded-lg border px-3 py-2">
                  {ORDER_STATUSES.map((x) => (
                    <option key={x.value} value={x.value}>
                      {x.label}
                    </option>
                  ))}
                </Field>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Trạng thái thanh toán</label>
                <Field as="select" name="payment_status" className="w-full rounded-lg border px-3 py-2">
                  {PAYMENT_STATUSES.map((x) => (
                    <option key={x.value} value={x.value}>
                      {x.label}
                    </option>
                  ))}
                </Field>
              </div>
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">Ghi chú</label>
              <Field as="textarea" name="notes" rows={2} className="w-full rounded-lg border px-3 py-2" />
            </div>

            <div className="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">
              <p>Tổng tiền hàng: {formatCurrency(total)}</p>
              <p>Thành tiền: {formatCurrency(finalAmount)}</p>
            </div>

            <div className="flex justify-end gap-3">
              <button type="button" className="rounded border px-4 py-2" onClick={onCancel}>
                Hủy
              </button>
              <button type="submit" className="rounded bg-primary px-4 py-2 text-white disabled:opacity-60" disabled={isSubmitting}>
                {isSubmitting ? 'Đang tạo...' : 'Tạo đơn'}
              </button>
            </div>
          </Form>
        );
      }}
    </Formik>
  );
}
