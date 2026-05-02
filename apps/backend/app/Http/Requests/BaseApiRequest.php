<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute là bắt buộc.',
            'string' => ':attribute phải là chuỗi ký tự.',
            'integer' => ':attribute phải là số nguyên.',
            'numeric' => ':attribute phải là số.',
            'email' => ':attribute không đúng định dạng email.',
            'unique' => ':attribute đã tồn tại.',
            'exists' => ':attribute không hợp lệ.',
            'min' => ':attribute không được nhỏ hơn :min.',
            'max' => ':attribute không được lớn hơn :max.',
            'array' => ':attribute phải là mảng.',
            'in' => ':attribute không hợp lệ.',
            'date' => ':attribute không đúng định dạng ngày.',
            'date_format' => ':attribute không đúng định dạng.',
            'confirmed' => ':attribute xác nhận không khớp.',
        ];
    }
}
