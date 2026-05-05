<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(Request $request)
    {
        $query = User::query()->with('warehouses');

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($request->filled('warehouse_id')) {
            $warehouseId = (int) $request->input('warehouse_id');
            $query->whereHas('warehouses', fn ($q) => $q->where('warehouses.id', $warehouseId));
        }

        if ($request->filled('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $users = $query->latest()->paginate($perPage);
        $users->setCollection(collect(UserResource::collection($users->getCollection())->resolve()));

        return $this->paginatedResponse($users, 'Lấy danh sách người dùng thành công');
    }

    public function store(StoreUserRequest $request)
    {
        $payload = $request->validated();

        try {
            $user = $this->userService->createUser($payload);

            logActivity('create_user', "Tạo người dùng: {$user->name}", 'users', $user->id);

            return $this->successResponse((new UserResource($user))->resolve(), 'Tạo người dùng thành công', 201);
        } catch (\Throwable $exception) {
            return $this->errorResponse('Không thể tạo người dùng: '.$exception->getMessage(), null, 500);
        }
    }

    public function show(User $user)
    {
        return $this->successResponse((new UserResource($user->load('warehouses')))->resolve(), 'Lấy thông tin người dùng thành công');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $payload = $request->validated();

        try {
            $trackedFields = ['name', 'email', 'role', 'phone', 'is_active'];
            $changes = [];

            foreach ($trackedFields as $field) {
                if (! array_key_exists($field, $payload)) {
                    continue;
                }

                $oldValue = $field === 'role'
                    ? ($user->role?->value ?? null)
                    : $user->{$field};
                $newValue = $payload[$field];

                if ($oldValue != $newValue) {
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }

            if (array_key_exists('warehouse_ids', $payload)) {
                $oldWarehouseIds = $user->warehouses()->pluck('warehouses.id')->sort()->values()->toArray();
                $newWarehouseIds = collect($payload['warehouse_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->toArray();

                if ($oldWarehouseIds !== $newWarehouseIds) {
                    $changes['warehouse_ids'] = [
                        'old' => $oldWarehouseIds,
                        'new' => $newWarehouseIds,
                    ];
                }
            }

            $user = $this->userService->updateUser($user, $payload);

            logActivity('update_user', "Cập nhật người dùng: {$user->name}", 'users', $user->id, $changes ?: null);

            return $this->successResponse((new UserResource($user))->resolve(), 'Cập nhật người dùng thành công');
        } catch (\Throwable $exception) {
            return $this->errorResponse('Không thể cập nhật người dùng: '.$exception->getMessage(), null, 500);
        }
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin() && User::query()->where('role', UserRole::ADMIN->value)->count() === 1) {
            return $this->errorResponse('Không thể xóa Admin cuối cùng trong hệ thống', null, 400);
        }

        $userName = $user->name;
        $userId = $user->id;

        $user->delete();

        logActivity('delete_user', "Xóa người dùng: {$userName}", 'users', $userId);

        return $this->successResponse(null, 'Xóa người dùng thành công');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_password.required' => 'Mật khẩu mới không được để trống',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
        ]);

        $this->userService->changePassword($user, $request->input('new_password'));

        logActivity('reset_password', "Đặt lại mật khẩu cho: {$user->name}", 'users', $user->id);

        return $this->successResponse(null, 'Đặt lại mật khẩu thành công');
    }

    public function lock(User $user)
    {
        if ($user->isAdmin()) {
            return $this->errorResponse('Không thể khóa tài khoản Admin', null, 400);
        }

        $user->lock();

        logActivity('lock_user', "Khóa tài khoản: {$user->name}", 'users', $user->id);

        return $this->successResponse(null, 'Khóa tài khoản thành công');
    }

    public function unlock(User $user)
    {
        $user->unlock();

        logActivity('unlock_user', "Mở khóa tài khoản: {$user->name}", 'users', $user->id);

        return $this->successResponse(null, 'Mở khóa tài khoản thành công');
    }

    public function assignWarehouses(Request $request, User $user)
    {
        $request->validate([
            'warehouse_ids' => ['required', 'array'],
            'warehouse_ids.*' => ['required', 'exists:warehouses,id'],
        ], [
            'warehouse_ids.required' => 'Vui lòng chọn ít nhất một kho',
            'warehouse_ids.array' => 'Danh sách kho không hợp lệ',
            'warehouse_ids.*.exists' => 'Kho được chọn không tồn tại',
        ]);

        $warehouseIds = collect($request->input('warehouse_ids', []))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $user = $this->userService->updateUser($user, ['warehouse_ids' => $warehouseIds]);

        logActivity('assign_warehouses', "Phân quyền kho cho: {$user->name}", 'users', $user->id, [
            'warehouse_ids' => $warehouseIds,
        ]);

        return $this->successResponse((new UserResource($user))->resolve(), 'Phân quyền kho thành công');
    }

    public function activityLogs(User $user, Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $logs = $user->activityLogs()->latest()->paginate($perPage);

        return $this->paginatedResponse($logs, 'Lấy lịch sử hoạt động thành công');
    }
}
