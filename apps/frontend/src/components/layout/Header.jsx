import { FiLogOut, FiMenu, FiUser } from 'react-icons/fi';
import { useWarehouse } from '../../contexts/WarehouseContext';

export default function Header({ onToggleSidebar, user, onLogout }) {
  const { currentWarehouse, warehouses, loading, switchWarehouse } = useWarehouse();

  return (
    <header className="sticky top-0 z-20 flex items-center justify-between border-b bg-white px-4 py-3 shadow-sm lg:px-6">
      <div className="flex items-center gap-3">
        <button className="rounded border p-2 lg:hidden" onClick={onToggleSidebar}>
          <FiMenu />
        </button>
        <h1 className="text-lg font-semibold text-slate-800">Trang quản trị</h1>
      </div>

      <div className="flex items-center gap-3">
        <div className="hidden items-center gap-2 md:flex">
          <label htmlFor="warehouse-select" className="text-sm text-slate-500">
            Kho:
          </label>
          <select
            id="warehouse-select"
            value={currentWarehouse?.id || ''}
            disabled={loading || warehouses.length === 0}
            onChange={(event) => {
              const selectedId = Number(event.target.value);
              const selectedWarehouse = warehouses.find((warehouse) => warehouse.id === selectedId);
              if (selectedWarehouse) {
                switchWarehouse(selectedWarehouse);
              }
            }}
            className="min-w-40 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-100"
          >
            {!currentWarehouse && <option value="">Chọn kho</option>}
            {warehouses.map((warehouse) => (
              <option key={warehouse.id} value={warehouse.id}>
                {warehouse.name}
              </option>
            ))}
          </select>
        </div>

        <div className="hidden items-center gap-2 text-sm text-slate-600 sm:flex">
          <FiUser />
          <span>{user?.name || 'Người dùng'}</span>
        </div>

        <button className="flex items-center gap-2 rounded bg-slate-100 px-3 py-2 text-sm hover:bg-slate-200" onClick={onLogout}>
          <FiLogOut /> Đăng xuất
        </button>
      </div>
    </header>
  );
}
