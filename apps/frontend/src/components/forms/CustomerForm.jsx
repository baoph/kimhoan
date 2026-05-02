import { Formik, Form, Field, ErrorMessage } from 'formik';
import * as Yup from 'yup';
import { GENDER_OPTIONS } from '../../utils/constants';

const schema = Yup.object({
  customer_code: Yup.string().required('Bắt buộc'),
  name: Yup.string().required('Bắt buộc'),
  email: Yup.string().email('Email không hợp lệ').nullable(),
});

const defaults = {
  customer_code: '',
  name: '',
  phone1: '',
  phone2: '',
  email: '',
  facebook: '',
  address: '',
  district: '',
  ward: '',
  gender: '',
  birth_date: '',
  notes: '',
};

export default function CustomerForm({ initialData, onSubmit, onCancel }) {
  return (
    <Formik initialValues={{ ...defaults, ...initialData }} validationSchema={schema} enableReinitialize onSubmit={onSubmit}>
      {({ isSubmitting }) => (
        <Form className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            {[
              ['customer_code', 'Mã khách hàng'],
              ['name', 'Tên khách hàng'],
              ['phone1', 'Điện thoại 1'],
              ['phone2', 'Điện thoại 2'],
              ['email', 'Email'],
              ['facebook', 'Facebook'],
            ].map(([name, label]) => (
              <div key={name}>
                <label className="mb-1 block text-sm font-medium">{label}</label>
                <Field name={name} className="w-full rounded-lg border px-3 py-2" />
                <ErrorMessage name={name} component="div" className="mt-1 text-xs text-red-600" />
              </div>
            ))}

            <div>
              <label className="mb-1 block text-sm font-medium">Giới tính</label>
              <Field as="select" name="gender" className="w-full rounded-lg border px-3 py-2">
                <option value="">Chọn giới tính</option>
                {GENDER_OPTIONS.map((item) => (
                  <option key={item.value} value={item.value}>
                    {item.label}
                  </option>
                ))}
              </Field>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Sinh nhật</label>
              <Field type="date" name="birth_date" className="w-full rounded-lg border px-3 py-2" />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            <div>
              <label className="mb-1 block text-sm font-medium">Địa chỉ</label>
              <Field name="address" className="w-full rounded-lg border px-3 py-2" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Khu vực</label>
              <Field name="district" className="w-full rounded-lg border px-3 py-2" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Phường/Xã</label>
              <Field name="ward" className="w-full rounded-lg border px-3 py-2" />
            </div>
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">Ghi chú</label>
            <Field as="textarea" name="notes" rows={3} className="w-full rounded-lg border px-3 py-2" />
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
