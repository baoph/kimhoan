<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\BaseApiRequest;

class UpdatePurchaseOrderRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['sometimes', 'required', 'exists:warehouses,id'],
            'supplier_id' => ['sometimes', 'required', 'exists:suppliers,id'],
            'order_date' => ['sometimes', 'required', 'date'],
            'status' => ['nullable', 'in:draft,pending,completed,cancelled'],
            'notes' => ['nullable', 'string'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'kho',
            'supplier_id' => 'nhà cung cấp',
            'order_date' => 'ngày nhập',
            'status' => 'trạng thái',
            'notes' => 'ghi chú',
            'items' => 'danh sách sản phẩm',
            'items.*.product_id' => 'sản phẩm',
            'items.*.quantity' => 'số lượng',
            'items.*.unit_price' => 'giá nhập',
        ];
    }
}
