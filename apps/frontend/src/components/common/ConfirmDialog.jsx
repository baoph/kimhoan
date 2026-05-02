import Modal from './Modal';

export default function ConfirmDialog({ open, onClose, onConfirm, title, message, loading }) {
  return (
    <Modal open={open} onClose={onClose} title={title || 'Xác nhận'} width="max-w-md">
      <p className="mb-6 text-slate-700">{message || 'Bạn chắc chắn muốn thực hiện thao tác này?'}</p>
      <div className="flex justify-end gap-3">
        <button className="rounded border px-4 py-2" onClick={onClose}>
          Hủy
        </button>
        <button className="rounded bg-red-600 px-4 py-2 text-white disabled:opacity-60" onClick={onConfirm} disabled={loading}>
          {loading ? 'Đang xử lý...' : 'Xác nhận'}
        </button>
      </div>
    </Modal>
  );
}
