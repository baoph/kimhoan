<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\BaseApiRequest;

class StorePurchaseOrderRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'status' => ['nullable', 'in:draft,pending,completed,cancelled'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
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
