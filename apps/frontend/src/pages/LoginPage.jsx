import { Formik, Form, Field, ErrorMessage } from 'formik';
import * as Yup from 'yup';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { FiLogIn } from 'react-icons/fi';
import { toast } from 'react-toastify';
import { useWarehouse } from '../contexts/WarehouseContext';

const schema = Yup.object({
  email: Yup.string().email('Email không hợp lệ').required('Vui lòng nhập email'),
  password: Yup.string().required('Vui lòng nhập mật khẩu'),
});

export default function LoginPage() {
  const { login } = useAuth();
  const { refreshWarehouses } = useWarehouse();
  const navigate = useNavigate();

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary to-primaryDark px-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
        <h2 className="mb-2 text-2xl font-bold text-slate-800">Đăng nhập hệ thống</h2>
        <p className="mb-6 text-sm text-slate-500">Vui lòng đăng nhập để quản lý cửa hàng</p>

        <Formik
          initialValues={{ email: '', password: '' }}
          validationSchema={schema}
          onSubmit={async (values, { setSubmitting }) => {
            try {
              const loginResponse = await login(values);
              const loggedUser = loginResponse?.data?.data?.user;

              await refreshWarehouses({ authenticatedUser: loggedUser });
              navigate('/', { replace: true });
            } catch (error) {
              console.error('[LoginPage] Login failed:', error?.response?.data || error.message);
              toast.error(error.response?.data?.message || 'Đăng nhập thất bại');
            } finally {
              setSubmitting(false);
            }
          }}
        >
          {({ isSubmitting }) => (
            <Form className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium">Email</label>
                <Field name="email" type="email" className="w-full rounded-lg border px-3 py-2 outline-none focus:border-primary" />
                <ErrorMessage name="email" component="div" className="mt-1 text-xs text-red-600" />
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium">Mật khẩu</label>
                <Field name="password" type="password" className="w-full rounded-lg border px-3 py-2 outline-none focus:border-primary" />
                <ErrorMessage name="password" component="div" className="mt-1 text-xs text-red-600" />
              </div>

              <button
                type="submit"
                className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-white hover:bg-primaryDark disabled:opacity-60"
                disabled={isSubmitting}
              >
                <FiLogIn /> {isSubmitting ? 'Đang đăng nhập...' : 'Đăng nhập'}
              </button>
            </Form>
          )}
        </Formik>

        <div className="mt-5 rounded-lg bg-blue-50 p-3 text-sm text-blue-700">Tài khoản mẫu: admin@kimhoan.local / 12345678</div>
      </div>
    </div>
  );
}
