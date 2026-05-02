<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->with(['customerGroup', 'creator']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone1', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($groupId = $request->input('customer_group_id')) {
            $query->where('customer_group_id', $groupId);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $customers = $query->latest()->paginate($perPage);

        return $this->paginatedResponse($customers, 'Lấy danh sách khách hàng thành công');
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated() + [
            'created_by' => $request->user()?->id,
        ]);

        return $this->successResponse(
            $customer->load(['customerGroup', 'creator']),
            'Tạo khách hàng thành công',
            201
        );
    }

    public function show(Customer $customer)
    {
        return $this->successResponse(
            $customer->load(['customerGroup', 'creator', 'orders']),
            'Lấy chi tiết khách hàng thành công'
        );
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return $this->successResponse(
            $customer->load(['customerGroup', 'creator']),
            'Cập nhật khách hàng thành công'
        );
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return $this->successResponse(null, 'Xóa khách hàng thành công');
    }
}
