import { ErrorMessage, Field, Form, Formik } from 'formik';
import * as Yup from 'yup';
import Modal from '../../../components/common/Modal';

const buildSchema = (isEdit) =>
  Yup.object({
    name: Yup.string().trim().required('Vui lòng nhập tên nhân viên'),
    email: Yup.string().trim().email('Email không hợp lệ').required('Vui lòng nhập email'),
    password: isEdit
      ? Yup.string()
          .transform((value) => (value === '' ? null : value))
          .nullable()
          .min(6, 'Mật khẩu tối thiểu 6 ký tự')
      : Yup.string().min(6, 'Mật khẩu tối thiểu 6 ký tự').required('Vui lòng nhập mật khẩu'),
    role: Yup.string().oneOf(['admin', 'manager', 'staff'], 'Vai trò không hợp lệ').required('Vui lòng chọn vai trò'),
    warehouse_ids: Yup.array().of(Yup.number()),
    is_active: Yup.boolean(),
  });

const toNumberArray = (value) => (Array.isArray(value) ? value.map((item) => Number(item)).filter(Boolean) : []);

export default function UserFormModal({ open, onClose, user, warehouses = [], onSubmit }) {
  const isEdit = Boolean(user?.id);

  const initialValues = {
    name: user?.name || '',
    email: user?.email || '',
    password: '',
    role: user?.role || 'staff',
    warehouse_ids: toNumberArray(user?.warehouses?.map((warehouse) => warehouse.id)),
    is_active: user?.is_active ?? true,
  };

  return (
    <Modal open={open} onClose={onClose} title={isEdit ? 'Sửa thông tin nhân viên' : 'Thêm nhân viên'} width="max-w-3xl">
      <Formik
        initialValues={initialValues}
        validationSchema={buildSchema(isEdit)}
        enableReinitialize
        onSubmit={async (values, helpers) => {
          try {
            const payload = {
              name: values.name.trim(),
              email: values.email.trim(),
              role: values.role,
              is_active: Boolean(values.is_active),
              warehouse_ids: toNumberArray(values.warehouse_ids),
            };

            if (values.password?.trim()) {
              payload.password = values.password.trim();
            }

            await onSubmit?.(payload, helpers);
          } finally {
            helpers.setSubmitting(false);
          }
        }}
      >
        {({ values, setFieldValue, isSubmitting }) => (
          <Form className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <label className="mb-1 block text-sm font-medium">Tên nhân viên *</label>
                <Field name="name" className="w-full rounded-lg border px-3 py-2" placeholder="Nhập tên nhân viên" />
                <ErrorMessage name="name" component="div" className="mt-1 text-xs text-red-600" />
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium">Email *</label>
                <Field name="email" type="email" className="w-full rounded-lg border px-3 py-2" placeholder="email@domain.com" />
                <ErrorMessage name="email" component="div" className="mt-1 text-xs text-red-600" />
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <label className="mb-1 block text-sm font-medium">Mật khẩu {isEdit ? '' : '*'}</label>
                <Field
                  name="password"
                  type="password"
                  className="w-full rounded-lg border px-3 py-2"
                  placeholder={isEdit ? 'Để trống nếu không đổi' : 'Tối thiểu 6 ký tự'}
                />
                <ErrorMessage name="password" component="div" className="mt-1 text-xs text-red-600" />
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium">Vai trò *</label>
                <Field as="select" name="role" className="w-full rounded-lg border px-3 py-2">
                  <option value="admin">Admin</option>
                  <option value="manager">Quản lý</option>
                  <option value="staff">Nhân viên</option>
                </Field>
                <ErrorMessage name="role" component="div" className="mt-1 text-xs text-red-600" />
              </div>
            </div>

            <div>
              <p className="mb-2 text-sm font-medium">Kho được phân quyền</p>
              <div className="max-h-44 space-y-2 overflow-y-auto rounded-lg border bg-slate-50 p-3">
                {warehouses.length === 0 && <p className="text-sm text-slate-500">Chưa có kho nào để phân quyền</p>}
                {warehouses.map((warehouse) => {
                  const checked = values.warehouse_ids.includes(Number(warehouse.id));
                  return (
                    <label key={warehouse.id} className="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                      <input
                        type="checkbox"
                        className="h-4 w-4"
                        checked={checked}
                        onChange={(event) => {
                          if (event.target.checked) {
                            setFieldValue('warehouse_ids', [...values.warehouse_ids, Number(warehouse.id)]);
                          } else {
                            setFieldValue(
                              'warehouse_ids',
                              values.warehouse_ids.filter((id) => Number(id) !== Number(warehouse.id))
                            );
                          }
                        }}
                      />
                      <span>{warehouse.code ? `${warehouse.code} - ` : ''}{warehouse.name}</span>
                    </label>
                  );
                })}
              </div>
            </div>

            <label className="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
              <Field type="checkbox" name="is_active" className="h-4 w-4" />
              Trạng thái: {values.is_active ? 'Đang hoạt động' : 'Đã khóa'}
            </label>

            <div className="flex justify-end gap-3 border-t pt-4">
              <button type="button" className="rounded-lg border px-4 py-2" onClick={onClose} disabled={isSubmitting}>
                Hủy
              </button>
              <button type="submit" className="rounded-lg bg-primary px-4 py-2 text-white" disabled={isSubmitting}>
                {isSubmitting ? 'Đang lưu...' : isEdit ? 'Lưu thay đổi' : 'Tạo nhân viên'}
              </button>
            </div>
          </Form>
        )}
      </Formik>
    </Modal>
  );
}
