import { FiInbox } from 'react-icons/fi';

export default function EmptyState({ title = 'Không có dữ liệu', description = 'Chưa có bản ghi phù hợp', action }) {
  return (
    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
      <div className="mb-3 rounded-full bg-slate-200 p-3 text-slate-500">
        <FiInbox size={22} />
      </div>
      <h4 className="text-base font-semibold text-slate-700">{title}</h4>
      <p className="mt-1 text-sm text-slate-500">{description}</p>
      {action ? <div className="mt-4">{action}</div> : null}
    </div>
  );
}
