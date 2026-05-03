import { useEffect, useState } from 'react';
import { warehouseService } from '../../api/services';

export default function WarehouseSelect({ value, onChange, includeAllOption = false, className = '', disabled = false }) {
  const [warehouses, setWarehouses] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchWarehouses = async () => {
      setLoading(true);
      try {
        const res = await warehouseService.getAll({ per_page: 100, is_active: true });
        setWarehouses(res.data.data || []);
      } finally {
        setLoading(false);
      }
    };

    fetchWarehouses();
  }, []);

  return (
    <select
      value={value ?? ''}
      onChange={(e) => onChange?.(e.target.value)}
      className={`w-full rounded-lg border px-3 py-2 ${className}`}
      disabled={disabled || loading}
    >
      <option value="">{loading ? 'Đang tải kho...' : includeAllOption ? 'Tất cả kho' : 'Chọn kho'}</option>
      {warehouses.map((warehouse) => (
        <option key={warehouse.id} value={warehouse.id}>
          {warehouse.code} - {warehouse.name}
        </option>
      ))}
    </select>
  );
}
