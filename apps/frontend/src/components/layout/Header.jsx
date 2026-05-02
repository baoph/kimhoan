import { FiLogOut, FiMenu, FiUser } from 'react-icons/fi';

export default function Header({ onToggleSidebar, user, onLogout }) {
  return (
    <header className="sticky top-0 z-20 flex items-center justify-between border-b bg-white px-4 py-3 shadow-sm lg:px-6">
      <div className="flex items-center gap-3">
        <button className="rounded border p-2 lg:hidden" onClick={onToggleSidebar}>
          <FiMenu />
        </button>
        <h1 className="text-lg font-semibold text-slate-800">Trang quản trị</h1>
      </div>
      <div className="flex items-center gap-4">
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
