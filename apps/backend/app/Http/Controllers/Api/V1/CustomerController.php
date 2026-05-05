<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();

        $query = Customer::query()
            ->with(['customerGroup', 'creator'])
            ->where('warehouse_id', $warehouseId);

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
        $customers->setCollection(collect(CustomerResource::collection($customers->getCollection())->resolve()));

        return $this->paginatedResponse($customers, 'Lấy danh sách khách hàng thành công');
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated() + [
            'warehouse_id' => getCurrentWarehouseId(),
            'created_by' => $request->user()?->id,
        ]);

        return $this->successResponse(
            (new CustomerResource($customer->load(['customerGroup', 'creator', 'warehouse'])))->resolve(),
            'Tạo khách hàng thành công',
            201
        );
    }

    public function show(Request $request, Customer $customer)
    {
        if ($response = $this->ensureCustomerInWarehouse($request, $customer)) {
            return $response;
        }

        return $this->successResponse(
            (new CustomerResource($customer->load(['customerGroup', 'creator', 'orders', 'warehouse'])))->resolve(),
            'Lấy chi tiết khách hàng thành công'
        );
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        if ($response = $this->ensureCustomerInWarehouse($request, $customer)) {
            return $response;
        }

        $data = $request->validated();
        unset($data['warehouse_id']);

        $customer->update($data);

        return $this->successResponse(
            (new CustomerResource($customer->fresh()->load(['customerGroup', 'creator', 'warehouse'])))->resolve(),
            'Cập nhật khách hàng thành công'
        );
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ($response = $this->ensureCustomerInWarehouse($request, $customer)) {
            return $response;
        }

        $customer->delete();

        return $this->successResponse(null, 'Xóa khách hàng thành công');
    }

    private function ensureCustomerInWarehouse(Request $request, Customer $customer)
    {
        $warehouseId = (int) ($request->input('current_warehouse_id') ?? getCurrentWarehouseId());

        if ((int) $customer->warehouse_id !== $warehouseId) {
            return $this->errorResponse('Bạn không có quyền thao tác khách hàng của kho khác', [], 403);
        }

        return null;
    }
}
