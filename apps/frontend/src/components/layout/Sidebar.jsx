import { NavLink } from 'react-router-dom';
import { FiBarChart2, FiBox, FiClipboard, FiHome, FiSettings, FiShoppingCart, FiTruck, FiUsers } from 'react-icons/fi';

const menus = [
  { to: '/', label: 'Tổng quan', icon: FiHome },
  { to: '/products', label: 'Hàng hóa', icon: FiBox },
  { to: '/customers', label: 'Khách hàng', icon: FiUsers },
  { to: '/orders', label: 'Đơn hàng', icon: FiClipboard },
  { to: '/reports', label: 'Báo cáo', icon: FiBarChart2 },
];

const settingMenus = [
  { to: '/warehouses', label: 'Quản lý kho', icon: FiBox },
  { to: '/suppliers', label: 'Nhà cung cấp', icon: FiTruck },
  { to: '/purchase-orders', label: 'Phiếu nhập hàng', icon: FiShoppingCart },
];

export default function Sidebar({ open, onClose }) {
  return (
    <>
      <div className={`fixed inset-0 z-30 bg-black/30 lg:hidden ${open ? 'block' : 'hidden'}`} onClick={onClose} />
      <aside
        className={`fixed left-0 top-0 z-40 h-full w-64 overflow-y-auto bg-primary text-white transition-transform lg:translate-x-0 ${
          open ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="border-b border-white/20 px-5 py-4 text-xl font-bold">KiotViet Clone</div>

        <nav className="space-y-1 p-3">
          {menus.map(({ to, label, icon: Icon }) => (
            <NavLink
              key={to}
              to={to}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-lg px-3 py-2 transition ${
                  isActive ? 'bg-white text-primary font-semibold' : 'hover:bg-white/10'
                }`
              }
              onClick={onClose}
            >
              <Icon />
              {label}
            </NavLink>
          ))}
        </nav>

        <div className="mx-3 mt-3 rounded-lg bg-white/10 p-3">
          <div className="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-white/90">
            <FiSettings /> Cài đặt
          </div>

          <div className="space-y-1">
            {settingMenus.map(({ to, label, icon: Icon }) => (
              <NavLink
                key={to}
                to={to}
                className={({ isActive }) =>
                  `flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition ${
                    isActive ? 'bg-white text-primary font-semibold' : 'hover:bg-white/10'
                  }`
                }
                onClick={onClose}
              >
                <Icon />
                {label}
              </NavLink>
            ))}
          </div>
        </div>
      </aside>
    </>
  );
}
