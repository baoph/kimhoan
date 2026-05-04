const roleConfig = {
  admin: { label: 'Admin', className: 'bg-red-100 text-red-800' },
  manager: { label: 'Quản lý', className: 'bg-blue-100 text-blue-800' },
  staff: { label: 'Nhân viên', className: 'bg-gray-100 text-gray-800' },
};

export default function RoleBadge({ role }) {
  const normalizedRole = String(role || '').toLowerCase();
  const config = roleConfig[normalizedRole] || {
    label: role || '--',
    className: 'bg-slate-100 text-slate-700',
  };

  return <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${config.className}`}>{config.label}</span>;
}
