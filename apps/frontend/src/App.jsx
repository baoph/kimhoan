import { Route, Routes } from 'react-router-dom';
import MainLayout from './components/layout/MainLayout';
import ProtectedRoute from './routes/ProtectedRoute';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import ProductsPage from './pages/ProductsPage';
import CustomersPage from './pages/CustomersPage';
import OrdersPage from './pages/OrdersPage';
import ReportsPage from './pages/ReportsPage';
import UsersPage from './pages/Users/UsersPage';
import NotFoundPage from './pages/NotFoundPage';
import WarehousesPage from './pages/Warehouses/WarehousesPage';
import WarehouseStockPage from './pages/Warehouses/WarehouseStockPage';
import SuppliersPage from './pages/Suppliers/SuppliersPage';
import PurchaseOrdersPage from './pages/PurchaseOrders/PurchaseOrdersPage';
import CreatePurchaseOrderPage from './pages/PurchaseOrders/CreatePurchaseOrderPage';
import PurchaseOrderDetailPage from './pages/PurchaseOrders/PurchaseOrderDetailPage';
import LoadingSpinner from './components/common/LoadingSpinner';
import { useWarehouse } from './contexts/WarehouseContext';
import { useAuth } from './hooks/useAuth';

export default function App() {
  const { user, loading: authLoading, logout } = useAuth();
  const {
    loading: warehouseLoading,
    warehouses,
    currentWarehouse,
    error: warehouseError,
    refreshWarehouses,
  } = useWarehouse();

  if (authLoading || (user && warehouseLoading)) {
    return <LoadingSpinner fullPage />;
  }

  if (user && !warehouseLoading && warehouses.length === 0) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4">
        <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-card text-center space-y-4">
          <h2 className="text-xl font-semibold text-slate-800">Chưa có kho khả dụng</h2>
          <p className="text-sm text-slate-500">Tài khoản của bạn chưa được phân quyền vào kho nào. Vui lòng liên hệ quản trị viên.</p>
          <button
            type="button"
            onClick={logout}
            className="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-white hover:bg-primaryDark"
          >
            Đăng xuất
          </button>
        </div>
      </div>
    );
  }

  if (user && !warehouseLoading && warehouseError && !currentWarehouse) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4">
        <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-card text-center space-y-4">
          <h2 className="text-xl font-semibold text-slate-800">Không thể tải danh sách kho</h2>
          <p className="text-sm text-slate-500">Vui lòng thử tải lại dữ liệu hoặc đăng nhập lại.</p>
          <div className="flex justify-center gap-2">
            <button
              type="button"
              onClick={() => refreshWarehouses()}
              className="rounded-lg border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-100"
            >
              Thử lại
            </button>
            <button
              type="button"
              onClick={logout}
              className="rounded-lg bg-primary px-4 py-2 text-white hover:bg-primaryDark"
            >
              Đăng xuất
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />

      <Route
        path="/"
        element={
          <ProtectedRoute>
            <MainLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<DashboardPage />} />
        <Route path="products" element={<ProductsPage />} />
        <Route path="customers" element={<CustomersPage />} />
        <Route path="orders" element={<OrdersPage />} />
        <Route path="reports" element={<ReportsPage />} />
        <Route path="users" element={<UsersPage />} />

        <Route path="warehouses" element={<WarehousesPage />} />
        <Route path="warehouses/:id/stock" element={<WarehouseStockPage />} />
        <Route path="suppliers" element={<SuppliersPage />} />
        <Route path="purchase-orders" element={<PurchaseOrdersPage />} />
        <Route path="purchase-orders/create" element={<CreatePurchaseOrderPage />} />
        <Route path="purchase-orders/:id" element={<PurchaseOrderDetailPage />} />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
