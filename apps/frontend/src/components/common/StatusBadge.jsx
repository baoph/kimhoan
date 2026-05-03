const statusConfig = {
  draft: { label: 'Nháp', className: 'bg-slate-100 text-slate-700' },
  pending: { label: 'Chờ xử lý', className: 'bg-amber-100 text-amber-700' },
  completed: { label: 'Hoàn thành', className: 'bg-emerald-100 text-emerald-700' },
  cancelled: { label: 'Đã hủy', className: 'bg-red-100 text-red-700' },
  active: { label: 'Đang hoạt động', className: 'bg-emerald-100 text-emerald-700' },
  inactive: { label: 'Ngưng hoạt động', className: 'bg-slate-100 text-slate-700' },
};

export default function StatusBadge({ status, label }) {
  const normalizedStatus = String(status || '').toLowerCase();
  const config = statusConfig[normalizedStatus] || {
    label: label || status || '--',
    className: 'bg-slate-100 text-slate-700',
  };

  return (
    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${config.className}`}>
      {label || config.label}
    </span>
  );
}
