<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();

        $query = Order::query()
            ->with(['customer', 'warehouse', 'orderItems.product', 'staff'])
            ->where('warehouse_id', $warehouseId);

        if ($search = $request->string('search')->toString()) {
            $query->where('order_code', 'like', "%{$search}%");
        }

        if ($status = $request->input('order_status')) {
            $query->where('order_status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $orders = $query->latest('order_date')->paginate($perPage);
        $orders->setCollection(collect(OrderResource::collection($orders->getCollection())->resolve()));

        return $this->paginatedResponse($orders, 'Lấy danh sách đơn hàng thành công');
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder(
                $request->validated(),
                (int) getCurrentWarehouseId(),
                $request->user()?->id
            );

            return $this->successResponse((new OrderResource($order))->resolve(), 'Tạo đơn hàng thành công', 201);
        } catch (ValidationException $exception) {
            return $this->errorResponse('Dữ liệu gửi lên không hợp lệ', $exception->errors(), 422);
        } catch (Throwable $exception) {
            return $this->errorResponse('Không thể tạo đơn hàng', ['error' => $exception->getMessage()], 500);
        }
    }

    public function show(Request $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

        $order->load(['customer', 'staff', 'orderItems.product', 'warehouse']);

        return $this->successResponse((new OrderResource($order))->resolve(), 'Lấy chi tiết đơn hàng thành công');
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

        try {
            $order = $this->orderService->updateOrder($order, $request->validated(), (int) getCurrentWarehouseId());

            return $this->successResponse((new OrderResource($order))->resolve(), 'Cập nhật đơn hàng thành công');
        } catch (ValidationException $exception) {
            return $this->errorResponse('Dữ liệu gửi lên không hợp lệ', $exception->errors(), 422);
        } catch (Throwable $exception) {
            return $this->errorResponse('Không thể cập nhật đơn hàng', ['error' => $exception->getMessage()], 500);
        }
    }

    public function destroy(Request $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

        try {
            $this->orderService->deleteOrder($order);

            return $this->successResponse(null, 'Xóa đơn hàng thành công');
        } catch (ValidationException $exception) {
            return $this->errorResponse('Không thể xóa đơn hàng', $exception->errors(), 422);
        } catch (Throwable $exception) {
            return $this->errorResponse('Không thể xóa đơn hàng', ['error' => $exception->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($response = $this->ensureOrderInWarehouse($request, $order)) {
            return $response;
        }

        $validator = Validator::make(
            $request->all(),
            [
                'order_status' => ['required', Rule::in(OrderStatus::values())],
                'payment_status' => ['nullable', Rule::in(PaymentStatus::values())],
            ],
            [
                'required' => ':attribute là bắt buộc.',
                'in' => ':attribute không hợp lệ.',
            ],
            [
                'order_status' => 'Trạng thái đơn hàng',
                'payment_status' => 'Trạng thái thanh toán',
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse('Dữ liệu gửi lên không hợp lệ', $validator->errors(), 422);
        }

        $order->update($validator->validated());
        $order->load(['customer', 'staff', 'orderItems.product', 'warehouse']);

        return $this->successResponse((new OrderResource($order))->resolve(), 'Cập nhật trạng thái đơn hàng thành công');
    }

    private function ensureOrderInWarehouse(Request $request, Order $order)
    {
        $warehouseId = (int) ($request->input('current_warehouse_id') ?? getCurrentWarehouseId());

        if ((int) $order->warehouse_id !== $warehouseId) {
            return $this->errorResponse('Bạn không có quyền thao tác đơn hàng của kho khác', [], 403);
        }

        return null;
    }
}
