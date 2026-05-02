import { Formik, Form, Field, ErrorMessage } from 'formik';
import * as Yup from 'yup';

const schema = Yup.object({
  product_code: Yup.string().required('Bắt buộc'),
  name: Yup.string().required('Bắt buộc'),
  cost_price: Yup.number().min(0, '>=0').required('Bắt buộc'),
  selling_price: Yup.number().min(0, '>=0').required('Bắt buộc'),
  stock_quantity: Yup.number().min(0, '>=0'),
  min_stock: Yup.number().min(0, '>=0'),
  max_stock: Yup.number().min(0, '>=0'),
});

const defaultValues = {
  product_code: '',
  barcode: '',
  name: '',
  category_id: '',
  brand_id: '',
  cost_price: 0,
  selling_price: 0,
  stock_quantity: 0,
  min_stock: 0,
  max_stock: 999999,
  unit: 'cái',
  weight: 0,
  description: '',
  status: true,
};

export default function ProductForm({ initialData, categories, brands, onSubmit, onCancel }) {
  return (
    <Formik
      initialValues={{ ...defaultValues, ...initialData, category_id: initialData?.category_id || '', brand_id: initialData?.brand_id || '' }}
      validationSchema={schema}
      enableReinitialize
      onSubmit={(values, helpers) => onSubmit(values, helpers)}
    >
      {({ isSubmitting }) => (
        <Form className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            {[
              ['product_code', 'Mã hàng'],
              ['barcode', 'Mã vạch'],
              ['name', 'Tên hàng'],
              ['unit', 'Đơn vị'],
            ].map(([name, label]) => (
              <div key={name}>
                <label className="mb-1 block text-sm font-medium">{label}</label>
                <Field name={name} className="w-full rounded-lg border px-3 py-2" />
                <ErrorMessage name={name} component="div" className="mt-1 text-xs text-red-600" />
              </div>
            ))}

            <div>
              <label className="mb-1 block text-sm font-medium">Nhóm hàng</label>
              <Field as="select" name="category_id" className="w-full rounded-lg border px-3 py-2">
                <option value="">Chọn nhóm</option>
                {categories.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.name}
                  </option>
                ))}
              </Field>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Thương hiệu</label>
              <Field as="select" name="brand_id" className="w-full rounded-lg border px-3 py-2">
                <option value="">Chọn thương hiệu</option>
                {brands.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.name}
                  </option>
                ))}
              </Field>
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            {[
              ['cost_price', 'Giá vốn'],
              ['selling_price', 'Giá bán'],
              ['stock_quantity', 'Tồn kho'],
              ['min_stock', 'Định mức tối thiểu'],
              ['max_stock', 'Định mức tối đa'],
              ['weight', 'Trọng lượng'],
            ].map(([name, label]) => (
              <div key={name}>
                <label className="mb-1 block text-sm font-medium">{label}</label>
                <Field name={name} type="number" className="w-full rounded-lg border px-3 py-2" />
                <ErrorMessage name={name} component="div" className="mt-1 text-xs text-red-600" />
              </div>
            ))}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">Mô tả</label>
            <Field as="textarea" name="description" rows={3} className="w-full rounded-lg border px-3 py-2" />
          </div>

          <div className="flex items-center gap-2">
            <Field type="checkbox" name="status" />
            <span className="text-sm">Đang kinh doanh</span>
          </div>

          <div className="flex justify-end gap-3">
            <button type="button" className="rounded border px-4 py-2" onClick={onCancel}>
              Hủy
            </button>
            <button type="submit" className="rounded bg-primary px-4 py-2 text-white disabled:opacity-60" disabled={isSubmitting}>
              {isSubmitting ? 'Đang lưu...' : 'Lưu'}
            </button>
          </div>
        </Form>
      )}
    </Formik>
  );
}
