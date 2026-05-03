import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { toast } from 'react-toastify';
import { warehouseService } from '../api/services';
import { useAuth } from '../hooks/useAuth';

const WarehouseContext = createContext(null);

const STORAGE_KEY = 'current_warehouse_id';

const toArray = (response) => {
  const payload = response?.data?.data ?? response?.data;

  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;

  return [];
};

export function WarehouseProvider({ children }) {
  const { user } = useAuth();
  const [currentWarehouse, setCurrentWarehouse] = useState(null);
  const [warehouses, setWarehouses] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const hydrateCurrentWarehouse = useCallback((warehouseList) => {
    if (!warehouseList.length) {
      setCurrentWarehouse(null);
      localStorage.removeItem(STORAGE_KEY);
      return;
    }

    const savedWarehouseId = Number(localStorage.getItem(STORAGE_KEY));
    const selectedWarehouse = warehouseList.find((item) => item.id === savedWarehouseId) || warehouseList[0];

    setCurrentWarehouse(selectedWarehouse);
    localStorage.setItem(STORAGE_KEY, String(selectedWarehouse.id));
  }, []);

  const fetchWarehouses = useCallback(async () => {
    if (!user) {
      setWarehouses([]);
      setCurrentWarehouse(null);
      setLoading(false);
      setError(null);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await warehouseService.getAll({ per_page: 100 });
      const warehouseList = toArray(response).filter((item) => item?.is_active !== false);

      setWarehouses(warehouseList);
      hydrateCurrentWarehouse(warehouseList);

      if (!warehouseList.length) {
        toast.warn('Tài khoản của bạn chưa được gán kho hoạt động.');
      }
    } catch (err) {
      console.error('Không thể tải danh sách kho:', err);
      setWarehouses([]);
      setCurrentWarehouse(null);
      setError(err);
      toast.error(err.response?.data?.message || 'Không thể tải danh sách kho');
    } finally {
      setLoading(false);
    }
  }, [hydrateCurrentWarehouse, user]);

  useEffect(() => {
    fetchWarehouses();
  }, [fetchWarehouses]);

  const switchWarehouse = useCallback((warehouse) => {
    if (!warehouse || !warehouse.id) return;

    setCurrentWarehouse(warehouse);
    localStorage.setItem(STORAGE_KEY, String(warehouse.id));

    window.location.reload();
  }, []);

  const value = useMemo(
    () => ({
      currentWarehouse,
      warehouses,
      loading,
      error,
      switchWarehouse,
      refreshWarehouses: fetchWarehouses,
    }),
    [currentWarehouse, warehouses, loading, error, switchWarehouse, fetchWarehouses]
  );

  return <WarehouseContext.Provider value={value}>{children}</WarehouseContext.Provider>;
}

export function useWarehouse() {
  const context = useContext(WarehouseContext);

  if (!context) {
    throw new Error('useWarehouse phải được dùng trong WarehouseProvider');
  }

  return context;
}
