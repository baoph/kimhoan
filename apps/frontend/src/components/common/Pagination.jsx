export default function Pagination({ meta, onPageChange }) {
  if (!meta?.last_page || meta.last_page <= 1) return null;

  return (
    <div className="flex items-center justify-between border-t px-4 py-3 text-sm">
      <p className="text-slate-600">
        Tổng: <span className="font-semibold">{meta.total}</span>
      </p>
      <div className="flex gap-2">
        <button
          className="rounded border px-3 py-1 disabled:opacity-50"
          disabled={meta.current_page <= 1}
          onClick={() => onPageChange(meta.current_page - 1)}
        >
          Trước
        </button>
        <span className="px-2 py-1 text-slate-700">
          {meta.current_page}/{meta.last_page}
        </span>
        <button
          className="rounded border px-3 py-1 disabled:opacity-50"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onPageChange(meta.current_page + 1)}
        >
          Sau
        </button>
      </div>
    </div>
  );
}
