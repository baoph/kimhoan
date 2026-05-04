import { ErrorMessage, Field, Form, Formik } from 'formik';
import * as Yup from 'yup';
import Modal from '../../../components/common/Modal';

const schema = Yup.object({
  password: Yup.string().min(6, 'Mật khẩu tối thiểu 6 ký tự').required('Vui lòng nhập mật khẩu mới'),
  confirm_password: Yup.string()
    .oneOf([Yup.ref('password')], 'Mật khẩu xác nhận không khớp')
    .required('Vui lòng xác nhận mật khẩu'),
});

export default function ResetPasswordModal({ open, onClose, user, onSubmit }) {
  return (
    <Modal open={open} onClose={onClose} title="Đặt lại mật khẩu" width="max-w-md">
      <Formik
        initialValues={{ password: '', confirm_password: '' }}
        validationSchema={schema}
        enableReinitialize
        onSubmit={async (values, helpers) => {
          try {
            await onSubmit?.({ password: values.password });
            helpers.resetForm();
          } finally {
            helpers.setSubmitting(false);
          }
        }}
      >
        {({ isSubmitting }) => (
          <Form className="space-y-4">
            <p className="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
              Đặt lại mật khẩu cho: <span className="font-semibold text-slate-800">{user?.name || '--'}</span>
            </p>

            <div>
              <label className="mb-1 block text-sm font-medium">Mật khẩu mới</label>
              <Field type="password" name="password" className="w-full rounded-lg border px-3 py-2" />
              <ErrorMessage name="password" component="div" className="mt-1 text-xs text-red-600" />
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">Xác nhận mật khẩu</label>
              <Field type="password" name="confirm_password" className="w-full rounded-lg border px-3 py-2" />
              <ErrorMessage name="confirm_password" component="div" className="mt-1 text-xs text-red-600" />
            </div>

            <div className="flex justify-end gap-3 pt-2">
              <button type="button" className="rounded-lg border px-4 py-2" onClick={onClose} disabled={isSubmitting}>
                Hủy
              </button>
              <button type="submit" className="rounded-lg bg-primary px-4 py-2 text-white" disabled={isSubmitting}>
                {isSubmitting ? 'Đang xử lý...' : 'Xác nhận'}
              </button>
            </div>
          </Form>
        )}
      </Formik>
    </Modal>
  );
}
