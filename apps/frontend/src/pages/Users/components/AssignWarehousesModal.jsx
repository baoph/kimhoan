import { useEffect, useMemo, useState } from 'react';
import Modal from '../../../components/common/Modal';

const getAssignedIds = (user) =>
  (user?.warehouses || [])
    .map((warehouse) => Number(warehouse.id))
    .filter(Boolean);

export default function AssignWarehousesModal({ open, onClose, user, warehouses = [], onSubmit, loading = false }) {
  const defaultIds = useMemo(() => getAssignedIds(user), [user]);
  const [selectedIds, setSelectedIds] = useState(defaultIds);

  useEffect(() => {
    if (open) {
      setSelectedIds(defaultIds);
    }
  }, [defaultIds, open]);

  const toggleWarehouse = (warehouseId) => {
    const normalizedId = Number(warehouseId);
    if (selectedIds.includes(normalizedId)) {
      setSelectedIds(selectedIds.filter((id) => id !== normalizedId));
    } else {
      setSelectedIds([...selectedIds, normalizedId]);
    }
  };

  return (
    <Modal open={open} onClose={onClose} title="Phân quyền kho" width="max-w-xl">
      <div className="space-y-4">
        <p className="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
          Nhân viên: <span className="font-semibold text-slate-800">{user?.name || '--'}</span>
        </p>

        <div className="max-h-72 space-y-2 overflow-y-auto rounded-lg border bg-slate-50 p-3">
          {warehouses.length === 0 && <p className="text-sm text-slate-500">Không có kho nào để phân quyền</p>}
          {warehouses.map((warehouse) => (
            <label key={warehouse.id} className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-white">
              <input
                type="checkbox"
                className="h-4 w-4"
                checked={selectedIds.includes(Number(warehouse.id))}
                onChange={() => toggleWarehouse(warehouse.id)}
              />
              <span className="text-slate-700">{warehouse.code ? `${warehouse.code} - ` : ''}{warehouse.name}</span>
            </label>
          ))}
        </div>

        <div className="flex justify-end gap-3">
          <button type="button" className="rounded-lg border px-4 py-2" onClick={onClose} disabled={loading}>
            Hủy
          </button>
          <button
            type="button"
            className="rounded-lg bg-primary px-4 py-2 text-white disabled:opacity-60"
            disabled={loading}
            onClick={() => onSubmit?.({ warehouse_ids: selectedIds })}
          >
            {loading ? 'Đang lưu...' : 'Lưu phân quyền'}
          </button>
        </div>
      </div>
    </Modal>
  );
}
