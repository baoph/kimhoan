import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  FiActivity,
  FiClock,
  FiEdit,
  FiKey,
  FiLock,
  FiPlusCircle,
  FiTrash2,
  FiUnlock,
} from 'react-icons/fi';
import { toast } from 'react-toastify';
import { userService } from '../../../api/services';
import Modal from '../../../components/common/Modal';
import Pagination from '../../../components/common/Pagination';
import EmptyState from '../../../components/common/EmptyState';
import { formatDate } from '../../../utils/format';

const actionOptions = [
  { value: '', label: 'Tất cả hành động' },
  { value: 'create', label: 'Tạo mới' },
  { value: 'update', label: 'Cập nhật' },
  { value: 'delete', label: 'Xóa' },
  { value: 'reset_password', label: 'Đặt lại mật khẩu' },
  { value: 'lock', label: 'Khóa tài khoản' },
  { value: 'unlock', label: 'Mở khóa tài khoản' },
  { value: 'assign_warehouses', label: 'Phân quyền kho' },
  { value: 'login', label: 'Đăng nhập' },
];

const resolveActionIcon = (action = '') => {
  const normalizedAction = action.toLowerCase();
  if (normalizedAction.includes('create')) return FiPlusCircle;
  if (normalizedAction.includes('update')) return FiEdit;
  if (normalizedAction.includes('delete')) return FiTrash2;
  if (normalizedAction.includes('reset')) return FiKey;
  if (normalizedAction.includes('unlock')) return FiUnlock;
  if (normalizedAction.includes('lock')) return FiLock;
  if (normalizedAction.includes('login')) return FiClock;
  return FiActivity;
};

export default function ActivityLogsModal({ open, onClose, user }) {
  const [logs, setLogs] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [actionType, setActionType] = useState('');
  const [page, setPage] = useState(1);
  const [autoRefresh, setAutoRefresh] = useState(false);

  const fetchLogs = useCallback(
    async (targetPage = 1) => {
      if (!user?.id) return;
      setLoading(true);
      try {
        const params = {
          page: targetPage,
          per_page: 8,
          action: actionType || undefined,
        };
        const response = await userService.getActivityLogs(user.id, params);
        setLogs(response.data.data || []);
        setMeta(response.data.meta || null);
        setPage(targetPage);
      } catch (error) {
        toast.error(error.response?.data?.message || 'Không thể tải lịch sử hoạt động');
      } finally {
        setLoading(false);
      }
    },
    [actionType, user]
  );

  useEffect(() => {
    if (!open) return;
    fetchLogs(1);
  }, [open, actionType, fetchLogs]);

  useEffect(() => {
    if (!open || !autoRefresh) return undefined;
    const timer = setInterval(() => {
      fetchLogs(page);
    }, 20000);

    return () => clearInterval(timer);
  }, [autoRefresh, fetchLogs, open, page]);

  const actionLabelMap = useMemo(() => {
    return actionOptions.reduce((result, item) => {
      result[item.value] = item.label;
      return result;
    }, {});
  }, []);

  return (
    <Modal open={open} onClose={onClose} title={`Lịch sử hoạt động - ${user?.name || ''}`} width="max-w-4xl">
      <div className="space-y-4">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <select
            className="rounded-lg border px-3 py-2 text-sm"
            value={actionType}
            onChange={(event) => {
              setActionType(event.target.value);
              setPage(1);
            }}
          >
            {actionOptions.map((option) => (
              <option key={option.value || 'all'} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>

          <label className="inline-flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" className="h-4 w-4" checked={autoRefresh} onChange={(event) => setAutoRefresh(event.target.checked)} />
            Tự động làm mới (20 giây)
          </label>
        </div>

        {loading ? (
          <div className="space-y-2">
            {Array.from({ length: 4 }).map((_, idx) => (
              <div key={idx} className="h-16 animate-pulse rounded-lg bg-slate-100" />
            ))}
          </div>
        ) : logs.length === 0 ? (
          <EmptyState title="Chưa có lịch sử hoạt động" description="Nhân viên này chưa phát sinh thao tác trong hệ thống" />
        ) : (
          <ul className="space-y-3">
            {logs.map((log) => {
              const Icon = resolveActionIcon(log.action);
              const normalizedAction = String(log.action || '').toLowerCase();
              return (
                <li key={log.id} className="rounded-xl border bg-white p-3">
                  <div className="flex items-start gap-3">
                    <div className="rounded-lg bg-blue-50 p-2 text-primary">
                      <Icon size={16} />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-slate-800">{log.description || actionLabelMap[normalizedAction] || 'Thao tác hệ thống'}</p>
                      <p className="mt-1 text-xs text-slate-500">
                        {formatDate(log.created_at)} • Module: {log.module || '--'}
                      </p>
                      {log.ip_address ? <p className="mt-1 text-xs text-slate-400">IP: {log.ip_address}</p> : null}
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
        )}

        <Pagination meta={meta} onPageChange={fetchLogs} />
      </div>
    </Modal>
  );
}
