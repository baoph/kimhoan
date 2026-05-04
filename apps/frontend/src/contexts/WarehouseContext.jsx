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
  const { user, loading: authLoading } = useAuth();
  const [currentWarehouse, setCurrentWarehouse] = useState(null);
  const [warehouses, setWarehouses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const resetWarehouseState = useCallback(() => {
    setCurrentWarehouse(null);
    setWarehouses([]);
    setError(null);
    localStorage.removeItem(STORAGE_KEY);
  }, []);

  const hydrateCurrentWarehouse = useCallback((warehouseList) => {
    if (!warehouseList.length) {
      setCurrentWarehouse(null);
      localStorage.removeItem(STORAGE_KEY);
      return null;
    }

    const savedWarehouseId = Number(localStorage.getItem(STORAGE_KEY));
    const selectedWarehouse = warehouseList.find((item) => item.id === savedWarehouseId) || warehouseList[0];

    setCurrentWarehouse(selectedWarehouse);
    localStorage.setItem(STORAGE_KEY, String(selectedWarehouse.id));

    return selectedWarehouse;
  }, []);

  const fetchWarehouses = useCallback(
    async (options = {}) => {
      const authenticatedUser = options.authenticatedUser ?? user;

      if (!authenticatedUser) {
        resetWarehouseState();
        setLoading(false);

        return {
          ok: false,
          reason: 'unauthenticated',
        };
      }

      setLoading(true);
      setError(null);

      try {
        const response = await warehouseService.getAll({ per_page: 100 });
        const warehouseList = toArray(response).filter((item) => item?.is_active !== false);

        setWarehouses(warehouseList);
        const selectedWarehouse = hydrateCurrentWarehouse(warehouseList);

        if (!warehouseList.length) {
          toast.warn('Tài khoản của bạn chưa được gán kho hoạt động.');

          return {
            ok: false,
            reason: 'no_warehouses',
            warehouses: [],
          };
        }

        return {
          ok: true,
          warehouses: warehouseList,
          currentWarehouse: selectedWarehouse,
        };
      } catch (err) {
        console.error('Không thể tải danh sách kho:', err);
        setWarehouses([]);
        setCurrentWarehouse(null);
        setError(err);
        localStorage.removeItem(STORAGE_KEY);
        toast.error(err.response?.data?.message || 'Không thể tải danh sách kho');

        return {
          ok: false,
          reason: 'api_error',
          error: err,
        };
      } finally {
        setLoading(false);
      }
    },
    [hydrateCurrentWarehouse, resetWarehouseState, user]
  );

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!user) {
      resetWarehouseState();
      setLoading(false);
      return;
    }

    fetchWarehouses();
  }, [authLoading, fetchWarehouses, resetWarehouseState, user]);

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
