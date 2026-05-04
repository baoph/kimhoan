import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  FiActivity,
  FiChevronDown,
  FiEdit,
  FiKey,
  FiLock,
  FiPlus,
  FiTrash,
  FiUnlock,
  FiUserCheck,
} from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { userService, warehouseService } from '../../api/services';
import EmptyState from '../../components/common/EmptyState';
import Pagination from '../../components/common/Pagination';
import RoleBadge from '../../components/common/RoleBadge';
import StatusBadge from '../../components/common/StatusBadge';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { useAuth } from '../../hooks/useAuth';
import { formatDate } from '../../utils/format';
import ActivityLogsModal from './components/ActivityLogsModal';
import AssignWarehousesModal from './components/AssignWarehousesModal';
import ResetPasswordModal from './components/ResetPasswordModal';
import UserFormModal from './components/UserFormModal';

const initialFilters = {
  search: '',
  role: '',
  status: '',
  warehouse_id: '',
};

export default function UsersPage() {
  const navigate = useNavigate();
  const { user } = useAuth();

  const [users, setUsers] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);

  const [filters, setFilters] = useState(initialFilters);
  const [warehouses, setWarehouses] = useState([]);

  const [actionMenuUserId, setActionMenuUserId] = useState(null);
  const [formModal, setFormModal] = useState({ open: false, user: null });
  const [resetPasswordUser, setResetPasswordUser] = useState(null);
  const [assignWarehouseUser, setAssignWarehouseUser] = useState(null);
  const [activityUser, setActivityUser] = useState(null);

  const [confirmState, setConfirmState] = useState({
    open: false,
    type: null,
    user: null,
    loading: false,
  });

  const [assignLoading, setAssignLoading] = useState(false);

  const fetchWarehouses = useCallback(async () => {
    try {
      const response = await warehouseService.getAll({ per_page: 100 });
      setWarehouses(response.data.data || []);
    } catch {
      toast.error('Không thể tải danh sách kho');
    }
  }, []);

  const fetchUsers = useCallback(
    async (targetPage = 1) => {
      setLoading(true);
      try {
        const params = {
          page: targetPage,
          per_page: 10,
          search: filters.search || undefined,
          role: filters.role || undefined,
          warehouse_id: filters.warehouse_id || undefined,
          is_active:
            filters.status === ''
              ? undefined
              : filters.status === 'active',
        };

        const response = await userService.getAll(params);
        setUsers(response.data.data || []);
        setMeta(response.data.meta || null);
        setPage(targetPage);
      } catch (error) {
        toast.error(error.response?.data?.message || 'Không thể tải danh sách nhân viên');
      } finally {
        setLoading(false);
      }
    },
    [filters.role, filters.search, filters.status, filters.warehouse_id]
  );

  useEffect(() => {
    fetchUsers(1);
  }, [fetchUsers]);

  useEffect(() => {
    fetchWarehouses();
  }, [fetchWarehouses]);

  useEffect(() => {
    if (user && user.role !== 'admin') {
      toast.error('Bạn không có quyền truy cập trang này');
      navigate('/');
    }
  }, [navigate, user]);

  useEffect(() => {
    const closeMenu = () => setActionMenuUserId(null);
    window.addEventListener('click', closeMenu);
    return () => window.removeEventListener('click', closeMenu);
  }, []);

  const submitUserForm = async (payload, helpers) => {
    try {
      if (formModal.user) {
        await userService.update(formModal.user.id, payload);
        toast.success('Cập nhật nhân viên thành công');
      } else {
        await userService.create(payload);
        toast.success('Thêm nhân viên thành công');
      }

      setFormModal({ open: false, user: null });
      fetchUsers(page);
    } catch (error) {
      const backendErrors = error.response?.data?.errors;
      if (backendErrors) {
        Object.entries(backendErrors).forEach(([field, messages]) => helpers.setFieldError(field, messages?.[0]));
      }
      toast.error(error.response?.data?.message || 'Không thể lưu thông tin nhân viên');
    }
  };

  const handleResetPassword = async (payload) => {
    if (!resetPasswordUser) return;
    try {
      await userService.resetPassword(resetPasswordUser.id, payload);
      toast.success('Đặt lại mật khẩu thành công');
      setResetPasswordUser(null);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể đặt lại mật khẩu');
      throw error;
    }
  };

  const handleAssignWarehouses = async (payload) => {
    if (!assignWarehouseUser) return;
    setAssignLoading(true);
    try {
      await userService.assignWarehouses(assignWarehouseUser.id, payload);
      toast.success('Cập nhật phân quyền kho thành công');
      setAssignWarehouseUser(null);
      fetchUsers(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể phân quyền kho');
    } finally {
      setAssignLoading(false);
    }
  };

  const openConfirm = (type, selectedUser) => {
    setConfirmState({ open: true, type, user: selectedUser, loading: false });
  };

  const handleConfirmAction = async () => {
    if (!confirmState.user || !confirmState.type) return;
    setConfirmState((prev) => ({ ...prev, loading: true }));

    try {
      if (confirmState.type === 'delete') {
        await userService.delete(confirmState.user.id);
        toast.success('Xóa nhân viên thành công');
      }

      if (confirmState.type === 'lock') {
        await userService.lock(confirmState.user.id);
        toast.success('Đã khóa tài khoản nhân viên');
      }

      if (confirmState.type === 'unlock') {
        await userService.unlock(confirmState.user.id);
        toast.success('Đã mở khóa tài khoản nhân viên');
      }

      setConfirmState({ open: false, type: null, user: null, loading: false });
      fetchUsers(page);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Thao tác thất bại');
      setConfirmState((prev) => ({ ...prev, loading: false }));
    }
  };

  const confirmContent = useMemo(() => {
    if (confirmState.type === 'delete') {
      return {
        title: 'Xóa nhân viên',
        message: 'Bạn có chắc chắn muốn xóa nhân viên này?',
        confirmText: 'Xóa nhân viên',
        variant: 'danger',
      };
    }

    if (confirmState.type === 'lock') {
      return {
        title: 'Khóa tài khoản',
        message: 'Bạn có chắc chắn muốn khóa tài khoản nhân viên này?',
        confirmText: 'Khóa tài khoản',
        variant: 'warning',
      };
    }

    return {
      title: 'Mở khóa tài khoản',
      message: 'Bạn có chắc chắn muốn mở khóa tài khoản nhân viên này?',
      confirmText: 'Mở khóa tài khoản',
      variant: 'primary',
    };
  }, [confirmState.type]);

  const renderWarehouses = (rowUser) => {
    const assignedWarehouses = rowUser.warehouses || [];
    if (assignedWarehouses.length === 0) return <span className="text-slate-500">Chưa phân quyền</span>;

    if (assignedWarehouses.length <= 2) {
      return (
        <span>
          {assignedWarehouses.map((warehouse) => warehouse.name).join(', ')}
        </span>
      );
    }

    return <span>{assignedWarehouses.length} kho</span>;
  };

  const renderActionMenu = (rowUser) => (
    <div className="relative inline-flex">
      <button
        type="button"
        className="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
        onClick={(event) => {
          event.stopPropagation();
          setActionMenuUserId((prev) => (prev === rowUser.id ? null : rowUser.id));
        }}
      >
        Thao tác <FiChevronDown />
      </button>

      {actionMenuUserId === rowUser.id && (
        <div
          className="absolute right-0 top-10 z-10 w-52 rounded-lg border bg-white py-1 shadow-lg"
          onClick={(event) => event.stopPropagation()}
        >
          <button
            type="button"
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50"
            onClick={() => {
              setFormModal({ open: true, user: rowUser });
              setActionMenuUserId(null);
            }}
          >
            <FiEdit /> Sửa thông tin
          </button>
          <button
            type="button"
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50"
            onClick={() => {
              setResetPasswordUser(rowUser);
              setActionMenuUserId(null);
            }}
          >
            <FiKey /> Đặt lại mật khẩu
          </button>
          <button
            type="button"
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50"
            onClick={() => {
              setAssignWarehouseUser(rowUser);
              setActionMenuUserId(null);
            }}
          >
            <FiUserCheck /> Phân quyền kho
          </button>
          <button
            type="button"
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50"
            onClick={() => {
              setActivityUser(rowUser);
              setActionMenuUserId(null);
            }}
          >
            <FiActivity /> Xem lịch sử hoạt động
          </button>

          {rowUser.is_active ? (
            <button
              type="button"
              className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50"
              onClick={() => {
                openConfirm('lock', rowUser);
                setActionMenuUserId(null);
              }}
            >
              <FiLock /> Khóa tài khoản
            </button>
          ) : (
            <button
              type="button"
              className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50"
              onClick={() => {
                openConfirm('unlock', rowUser);
                setActionMenuUserId(null);
              }}
            >
              <FiUnlock /> Mở khóa tài khoản
            </button>
          )}

          <button
            type="button"
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
            onClick={() => {
              openConfirm('delete', rowUser);
              setActionMenuUserId(null);
            }}
          >
            <FiTrash /> Xóa nhân viên
          </button>
        </div>
      )}
    </div>
  );

  return (
    <div className="space-y-4 rounded-xl bg-white p-4 shadow-card lg:p-5">
      <div className="flex flex-col gap-3 border-b pb-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 className="text-xl font-semibold text-slate-800">Quản lý Nhân viên</h2>
          <p className="text-sm text-slate-500">Quản lý tài khoản và phân quyền kho cho nhân viên</p>
        </div>

        <button
          type="button"
          className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-white hover:bg-primaryDark"
          onClick={() => setFormModal({ open: true, user: null })}
        >
          <FiPlus /> Thêm nhân viên
        </button>
      </div>

      <div className="grid gap-2 lg:grid-cols-4">
        <input
          className="rounded-lg border px-3 py-2 lg:col-span-2"
          placeholder="Tìm kiếm theo tên hoặc email..."
          value={filters.search}
          onChange={(event) => setFilters((prev) => ({ ...prev, search: event.target.value }))}
        />

        <select
          className="rounded-lg border px-3 py-2"
          value={filters.role}
          onChange={(event) => setFilters((prev) => ({ ...prev, role: event.target.value }))}
        >
          <option value="">Tất cả vai trò</option>
          <option value="admin">Admin</option>
          <option value="manager">Quản lý</option>
          <option value="staff">Nhân viên</option>
        </select>

        <select
          className="rounded-lg border px-3 py-2"
          value={filters.status}
          onChange={(event) => setFilters((prev) => ({ ...prev, status: event.target.value }))}
        >
          <option value="">Tất cả trạng thái</option>
          <option value="active">Đang hoạt động</option>
          <option value="locked">Đã khóa</option>
        </select>

        <select
          className="rounded-lg border px-3 py-2"
          value={filters.warehouse_id}
          onChange={(event) => setFilters((prev) => ({ ...prev, warehouse_id: event.target.value }))}
        >
          <option value="">Tất cả kho</option>
          {warehouses.map((warehouse) => (
            <option key={warehouse.id} value={warehouse.id}>
              {warehouse.code ? `${warehouse.code} - ` : ''}{warehouse.name}
            </option>
          ))}
        </select>
      </div>

      <div className="overflow-x-auto rounded-xl border">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-100 text-left text-slate-700">
            <tr>
              <th className="px-4 py-3">Nhân viên</th>
              <th className="px-4 py-3">Email</th>
              <th className="px-4 py-3">Vai trò</th>
              <th className="px-4 py-3">Kho được phân quyền</th>
              <th className="px-4 py-3">Trạng thái</th>
              <th className="px-4 py-3">Đăng nhập cuối</th>
              <th className="px-4 py-3 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {loading &&
              Array.from({ length: 6 }).map((_, index) => (
                <tr key={index} className="border-t">
                  <td colSpan="7" className="px-4 py-3">
                    <div className="h-8 animate-pulse rounded bg-slate-100" />
                  </td>
                </tr>
              ))}

            {!loading &&
              users.map((rowUser, index) => (
                <tr
                  key={rowUser.id}
                  className={`border-t ${index % 2 === 0 ? 'bg-white' : 'bg-slate-50/40'} hover:bg-blue-50/40`}
                >
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-primary">
                        {rowUser?.name?.trim()?.charAt(0)?.toUpperCase() || 'U'}
                      </div>
                      <div className="font-medium text-slate-800">{rowUser.name}</div>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-slate-700">{rowUser.email}</td>
                  <td className="px-4 py-3">
                    <RoleBadge role={rowUser.role} />
                  </td>
                  <td className="px-4 py-3 text-slate-700">{renderWarehouses(rowUser)}</td>
                  <td className="px-4 py-3">
                    <StatusBadge type="user" isActive={Boolean(rowUser.is_active)} />
                  </td>
                  <td className="px-4 py-3 text-slate-600">{formatDate(rowUser.last_login_at)}</td>
                  <td className="px-4 py-3 text-right">{renderActionMenu(rowUser)}</td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      {!loading && users.length === 0 && (
        <EmptyState
          title="Chưa có nhân viên"
          description="Bấm 'Thêm nhân viên' để tạo tài khoản mới"
          action={
            <button
              type="button"
              className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-white"
              onClick={() => setFormModal({ open: true, user: null })}
            >
              <FiPlus /> Thêm nhân viên
            </button>
          }
        />
      )}

      <Pagination meta={meta} onPageChange={fetchUsers} />

      <UserFormModal
        open={formModal.open}
        onClose={() => setFormModal({ open: false, user: null })}
        user={formModal.user}
        warehouses={warehouses}
        onSubmit={submitUserForm}
      />

      <ResetPasswordModal
        open={Boolean(resetPasswordUser)}
        onClose={() => setResetPasswordUser(null)}
        user={resetPasswordUser}
        onSubmit={handleResetPassword}
      />

      <AssignWarehousesModal
        open={Boolean(assignWarehouseUser)}
        onClose={() => setAssignWarehouseUser(null)}
        user={assignWarehouseUser}
        warehouses={warehouses}
        loading={assignLoading}
        onSubmit={handleAssignWarehouses}
      />

      <ActivityLogsModal open={Boolean(activityUser)} onClose={() => setActivityUser(null)} user={activityUser} />

      <ConfirmDialog
        open={confirmState.open}
        onClose={() => setConfirmState({ open: false, type: null, user: null, loading: false })}
        onConfirm={handleConfirmAction}
        loading={confirmState.loading}
        title={confirmContent.title}
        message={confirmContent.message}
        confirmText={confirmContent.confirmText}
        variant={confirmContent.variant}
      />
    </div>
  );
}
