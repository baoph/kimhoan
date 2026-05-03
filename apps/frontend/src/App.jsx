import { Route, Routes } from 'react-router-dom';
import MainLayout from './components/layout/MainLayout';
import ProtectedRoute from './routes/ProtectedRoute';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import ProductsPage from './pages/ProductsPage';
import CustomersPage from './pages/CustomersPage';
import OrdersPage from './pages/OrdersPage';
import ReportsPage from './pages/ReportsPage';
import NotFoundPage from './pages/NotFoundPage';
import WarehousesPage from './pages/Warehouses/WarehousesPage';
import WarehouseStockPage from './pages/Warehouses/WarehouseStockPage';
import SuppliersPage from './pages/Suppliers/SuppliersPage';
import PurchaseOrdersPage from './pages/PurchaseOrders/PurchaseOrdersPage';
import CreatePurchaseOrderPage from './pages/PurchaseOrders/CreatePurchaseOrderPage';
import PurchaseOrderDetailPage from './pages/PurchaseOrders/PurchaseOrderDetailPage';

export default function App() {
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
