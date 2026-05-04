<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseApiRequest;

class StoreUserRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,manager,staff'],
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
            'password' => 'Mật khẩu',
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
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'email.unique' => 'Email đã tồn tại',
            'role.in' => 'Vai trò không hợp lệ',
        ]);
    }
}
