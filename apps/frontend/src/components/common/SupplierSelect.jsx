import { useEffect, useState } from 'react';
import { supplierService } from '../../api/services';

export default function SupplierSelect({ value, onChange, includeAllOption = false, className = '', disabled = false }) {
  const [suppliers, setSuppliers] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchSuppliers = async () => {
      setLoading(true);
      try {
        const res = await supplierService.getAll({ per_page: 100, is_active: true });
        setSuppliers(res.data.data || []);
      } finally {
        setLoading(false);
      }
    };

    fetchSuppliers();
  }, []);

  return (
    <select
      value={value ?? ''}
      onChange={(e) => onChange?.(e.target.value)}
      className={`w-full rounded-lg border px-3 py-2 ${className}`}
      disabled={disabled || loading}
    >
      <option value="">{loading ? 'Đang tải nhà cung cấp...' : includeAllOption ? 'Tất cả nhà cung cấp' : 'Chọn nhà cung cấp'}</option>
      {suppliers.map((supplier) => (
        <option key={supplier.id} value={supplier.id}>
          {supplier.supplier_code} - {supplier.name}
        </option>
      ))}
    </select>
  );
}
