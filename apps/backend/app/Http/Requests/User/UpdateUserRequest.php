<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$userId],
            'role' => ['sometimes', 'required', Rule::in(UserRole::values())],
            'warehouse_ids' => ['nullable', 'array'],
            'warehouse_ids.*' => ['nullable', 'exists:warehouses,id'],
            'is_active' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên người dùng',
            'email' => 'Email',
            'role' => 'Vai trò',
            'warehouse_ids' => 'Danh sách kho',
            'warehouse_ids.*' => 'Kho',
            'is_active' => 'Trạng thái hoạt động',
            'phone' => 'Số điện thoại',
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'email.unique' => 'Email đã tồn tại',
            'role.in' => 'Vai trò không hợp lệ',
        ]);
    }
}
