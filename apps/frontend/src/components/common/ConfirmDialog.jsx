import Modal from './Modal';

const variantClasses = {
  danger: 'bg-red-600 hover:bg-red-700',
  warning: 'bg-amber-500 hover:bg-amber-600',
  primary: 'bg-primary hover:bg-primaryDark',
};

export default function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title,
  message,
  loading,
  confirmText = 'Xác nhận',
  cancelText = 'Hủy',
  variant = 'danger',
}) {
  return (
    <Modal open={open} onClose={onClose} title={title || 'Xác nhận'} width="max-w-md">
      <p className="mb-6 text-slate-700">{message || 'Bạn chắc chắn muốn thực hiện thao tác này?'}</p>
      <div className="flex justify-end gap-3">
        <button className="rounded border px-4 py-2" onClick={onClose} disabled={loading}>
          {cancelText}
        </button>
        <button
          className={`rounded px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60 ${variantClasses[variant] || variantClasses.danger}`}
          onClick={onConfirm}
          disabled={loading}
        >
          {loading ? 'Đang xử lý...' : confirmText}
        </button>
      </div>
    </Modal>
  );
}
