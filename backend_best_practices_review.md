# Backend Best Practices Review (Laravel)

## Phạm vi review
- Project backend: `/home/ubuntu/kimhoan_review/apps/backend`
- Mục tiêu: verify 10 nhóm best practices theo checklist.
- Hình thức: static code review (không chạy được `php artisan` vì môi trường hiện tại không có PHP CLI).

## Tổng quan trạng thái

| # | Hạng mục | Trạng thái | Nhận định nhanh |
|---|---|---|---|
| 1 | Repository Pattern | ❌ Thiếu | Không có `app/Repositories`, controller thao tác Eloquent trực tiếp |
| 2 | Service Layer | ⚠️ Một phần | Không có `app/Services`; có tách Request validation nhưng business logic lớn trong controller |
| 3 | PHP Enums | ❌ Thiếu | Không có `app/Enums`; nhiều string literal cho status/role/gender |
| 4 | API Resources | ❌ Thiếu | Không có `app/Http/Resources`; trả về Model/Collection trực tiếp |
| 5 | Query Optimization | ⚠️ Một phần | Có eager loading ở nhiều chỗ nhưng còn pattern query trong vòng lặp (N+1 risk) |
| 6 | Eloquent Scopes | ❌ Thiếu | Không có `scope*` trong Models, query điều kiện lặp lại nhiều |
| 7 | Events & Listeners | ❌ Thiếu | Không có `app/Events`, `app/Listeners`, không thấy dispatch domain events |
| 8 | Migrations quality | ⚠️ Một phần | FK/index/timestamps khá ổn, nhưng thiếu chuẩn `onUpdate` và thiếu index cho vài cột lọc thường xuyên |
| 9 | Testing | ❌ Thiếu | Chỉ có test mẫu (`ExampleTest`), chưa có API endpoint tests thực tế |
|10| Rate Limiting | ❌ Thiếu | Không có cấu hình/áp middleware throttle rõ ràng cho API routes |

---

## 1) Repository Pattern — ❌ Thiếu

### Bằng chứng
- Không có folder `app/Repositories`.
- Không tìm thấy class/interface chứa từ khóa `Repository` trong `app/`.
- Controllers truy cập Model trực tiếp, ví dụ:
  - `OrderController`: `Order::query()`, `Product::findOrFail()`, `WarehouseStock::query()`.
  - `ProductController`: `Product::query()->with(...)`.

### Current code (đang có)
```php
// app/Http/Controllers/Api/V1/ProductController.php
$query = Product::query()->with([...]);
```

### Nên là
```php
// Controller
public function __construct(private ProductRepositoryInterface $products) {}

public function index(Request $request)
{
    $products = $this->products->paginateWithFilters($request->all());
    return ProductResource::collection($products);
}
```

---

## 2) Service Layer — ⚠️ Một phần

### Bằng chứng
- Không có folder `app/Services`.
- Các controller lớn chứa business flow phức tạp:
  - `OrderController.php` ~322 lines.
  - `PurchaseOrderController.php` ~334 lines.
  - `UserController.php` ~215 lines.
- Logic nghiệp vụ nằm trong controller: tính tiền, validate tồn kho, cập nhật stock, ghi inventory transaction, lock/unlock user, generate mã PO.

### Đánh giá Fat Controllers
- **Mức độ**: Cao ở `OrderController`/`PurchaseOrderController`.
- **Ảnh hưởng**: Khó test unit, khó tái sử dụng flow, tăng rủi ro regression khi sửa.

### Current code (đang có)
```php
// app/Http/Controllers/Api/V1/OrderController.php
$order = DB::transaction(function () use ($request, $warehouseId) {
    // validate stock
    // tính total
    // create order items
    // decrement stock
    // create inventory transaction
});
```

### Nên là
```php
// Controller
public function store(StoreOrderRequest $request, OrderService $service)
{
    $order = $service->createOrder($request->validated(), auth()->user(), getCurrentWarehouseId());
    return new OrderResource($order);
}

// Service
class OrderService {
    public function createOrder(array $data, User $user, int $warehouseId): Order {
        return DB::transaction(function () use ($data, $user, $warehouseId) {
            // orchestration nghiệp vụ ở đây
        });
    }
}
```

---

## 3) PHP Enums — ❌ Thiếu

### Bằng chứng
- Không có folder `app/Enums`.
- String literals cho trạng thái xuất hiện nhiều trong controllers/requests/models:
  - `OrderController`: `'draft','confirmed','completed','cancelled','returned'`, `'pending','paid','partial','refunded'`.
  - `PurchaseOrderController`: `'draft','pending','completed','cancelled'`.
  - `DashboardController`: lọc `'completed','returned','confirmed'`.
  - `CustomerRequest`: `'male','female','other'`.
  - `User`: role `'admin','manager','staff'`.
  - `InventoryTransaction`: `'sale','sale_return','purchase','purchase_cancel'`...

### Các chỗ nên dùng Enum
1. Order status
2. Payment status
3. Purchase order status
4. User role
5. Gender
6. Inventory transaction type

### Current code (đang có)
```php
'order_status' => ['nullable', 'in:draft,confirmed,completed,cancelled,returned']
```

### Nên là
```php
// app/Enums/OrderStatus.php
enum OrderStatus: string {
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';
}

// Request
use Illuminate\Validation\Rules\Enum;
'order_status' => ['nullable', new Enum(OrderStatus::class)]

// Model cast
protected function casts(): array
{
    return [
        'order_status' => OrderStatus::class,
    ];
}
```

---

## 4) API Resources — ❌ Thiếu

### Bằng chứng
- Không có folder `app/Http/Resources`.
- Không tìm thấy `JsonResource` / `new XxxResource(...)`.
- API hiện trả về model/object trực tiếp qua `successResponse`/`paginatedResponse`.

### Current code (đang có)
```php
return $this->successResponse($order->load(['customer', 'staff', 'orderItems.product', 'warehouse']));
```

### Nên là
```php
return $this->successResponse(new OrderResource($order->loadMissing([...])));
// hoặc
return OrderResource::collection($orders);
```

Lợi ích: chuẩn hóa schema response, ẩn field nhạy cảm, kiểm soát nested payload ổn định.

---

## 5) Query Optimization — ⚠️ Một phần

### Điểm tốt
- Có eager loading tại nhiều endpoint (`with(...)`) ví dụ `OrderController`, `ProductController`, `ReportController`, `DashboardController`, `UserController`.

### Vấn đề còn tồn tại (N+1 / query-in-loop)
- `OrderController::store`:
  - Vòng lặp tính total gọi `Product::findOrFail(...)` cho từng item.
  - Vòng lặp xử lý item tiếp tục query product/stock từng item.
- `OrderController::ensureStockAvailability` query product + stock theo từng item.
- `PurchaseOrderController::complete` và `cancel` query stock/product trong mỗi vòng lặp item.

### Current code (đang có)
```php
foreach ($items as $item) {
    $price = $item['unit_price'] ?? Product::findOrFail($item['product_id'])->selling_price;
}
```

### Nên là (batch load trước)
```php
$productIds = collect($items)->pluck('product_id')->unique()->values();
$products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
$stocks = WarehouseStock::query()
    ->where('warehouse_id', $warehouseId)
    ->whereIn('product_id', $productIds)
    ->lockForUpdate()
    ->get()
    ->keyBy('product_id');

foreach ($items as $item) {
    $product = $products[$item['product_id']] ?? null;
    // xử lý với dữ liệu đã load sẵn
}
```

---

## 6) Eloquent Scopes — ❌ Thiếu

### Bằng chứng
- Không có `scope*` methods trong `app/Models`.
- Điều kiện query lặp lại nhiều nơi:
  - `where('warehouse_id', $warehouseId)`
  - lọc status/date/search tương tự giữa nhiều controller.

### Nên bổ sung
- `scopeInWarehouse($query, int $warehouseId)`
- `scopeSearch($query, ?string $keyword)`
- `scopeStatus($query, ?string|Enum $status)`
- `scopeDateRange($query, $start, $end)`

### Mẫu
```php
// app/Models/Order.php
public function scopeInWarehouse($query, int $warehouseId)
{
    return $query->where('warehouse_id', $warehouseId);
}

public function scopeWithSummaryRelations($query)
{
    return $query->with(['customer:id,name,customer_code', 'staff:id,name']);
}
```

---

## 7) Events & Listeners — ❌ Thiếu

### Bằng chứng
- Không có `app/Events`.
- Không có `app/Listeners`.
- Không thấy `EventServiceProvider` trong `app/Providers` và `bootstrap/providers.php` chỉ load `AppServiceProvider`.
- Không thấy `event(...)`/`dispatch(...)` cho domain event.
- `OrderCreated`, `StockUpdated` chưa tồn tại.

### Nên là
- Event:
  - `OrderCreated`
  - `PurchaseOrderCompleted`
  - `StockUpdated`
- Listener:
  - `UpdateWarehouseStock`
  - `WriteInventoryTransaction`
  - `SendOrderNotification`

### Mẫu
```php
// Trong service
OrderCreated::dispatch($order);

// Listener handle
public function handle(OrderCreated $event): void
{
    // cập nhật tồn kho / ghi audit / gửi thông báo
}
```

---

## 8) Migrations — ⚠️ Một phần

### Điểm tốt
- Đa số bảng domain có foreign keys + timestamps.
- Có index ở nhiều bảng quan trọng (`orders`, `products`, `customers`, `purchase_orders`, `inventory_transactions`, `activity_logs`).
- Nhiều FK có `cascadeOnDelete`, `restrictOnDelete`, `nullOnDelete` rõ ràng.

### Điểm cần cải thiện
1. **Thiếu chuẩn `onUpdate`**
   - Không thấy `cascadeOnUpdate/restrictOnUpdate/...` trong migrations.
2. **Một số cột filter/search thường dùng chưa có index tối ưu** (theo code hiện tại):
   - `orders.payment_status` (lọc thường xuyên ở list).
   - `purchase_orders.supplier_id` + `warehouse_id` + `order_date/status` theo tổ hợp truy vấn thực tế.
   - `customers.email`, `customers.phone1` có index composite với name nhưng chưa tách rõ cho query độc lập.
3. `inventory_transactions` chỉ có `created_at` (không `updated_at`) là chấp nhận được cho log table, nhưng cần chuẩn hóa rõ convention trong tài liệu kỹ thuật.

### Current code (đang có)
```php
$table->index(['order_date', 'order_status']);
// chưa có index payment_status
```

### Nên là
```php
$table->index(['warehouse_id', 'order_date']);
$table->index(['warehouse_id', 'order_status']);
$table->index(['warehouse_id', 'payment_status']);
```

---

## 9) Testing — ❌ Thiếu

### Bằng chứng
- `tests/Feature`: 1 file (`ExampleTest.php`) — test GET `/` status 200.
- `tests/Unit`: 1 file (`ExampleTest.php`) — assert true.
- Chưa có test cho API endpoints `/api/v1/...` (auth, customers, products, orders, purchase-orders...).

### Nên bổ sung tối thiểu
1. Feature tests cho auth flow (login/profile/logout).
2. CRUD tests cho customers/products/orders.
3. Tests cho business critical flow:
   - tạo đơn hàng trừ tồn kho,
   - complete/cancel purchase order cập nhật stock đúng,
   - validate warehouse access.
4. Authorization tests (role: admin/manager/staff).

### Mẫu
```php
public function test_staff_cannot_access_admin_user_management(): void
{
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff, 'sanctum')
        ->getJson('/api/v1/users')
        ->assertForbidden();
}
```

---

## 10) Rate Limiting — ❌ Thiếu

### Bằng chứng
- Không có `app/Http/Kernel.php` (project theo cấu trúc Laravel mới, cấu hình middleware qua `bootstrap/app.php`).
- Không thấy `RateLimiter::for(...)` hoặc `Limit::perMinute(...)`.
- `routes/api.php` chưa apply throttle tường minh cho nhóm auth/public endpoints.

### Khuyến nghị
- Thiết lập rate limiter theo nhóm endpoint:
  - `auth` (login/register) nghiêm ngặt.
  - `api` business vừa phải.
  - `reports` giới hạn thấp hơn do query nặng.

### Mẫu
```php
// App\Providers\RouteServiceProvider hoặc bootstrap phù hợp phiên bản
RateLimiter::for('auth', fn (Request $request) => [
    Limit::perMinute(10)->by($request->ip()),
]);

// routes/api.php
Route::middleware('throttle:auth')->group(function () {
    Route::post('/v1/auth/login', ...);
    Route::post('/v1/auth/register', ...);
});
```

---

## Priority Implementation Roadmap

### P0 (Tuần 1) — High impact / Risk reduction
1. Bổ sung **Rate Limiting** cho auth + API.
2. Tách **Service Layer** cho `OrderController`, `PurchaseOrderController`.
3. Introduce **Enums** cho status/role/gender/transaction_type.
4. Viết **Feature tests** cho auth + order + purchase order flows.

### P1 (Tuần 2)
1. Tạo **API Resources** cho các entity chính (Order, Product, Customer, PurchaseOrder, User).
2. Bắt đầu **Repository Pattern** cho module phức tạp (Order, Product).
3. Tối ưu query theo batch-load, loại bỏ query-in-loop trong order/purchase order.

### P2 (Tuần 3)
1. Chuẩn hóa **Eloquent Scopes** cho warehouse/status/search/date range.
2. Tách domain side-effects bằng **Events & Listeners** (`OrderCreated`, `StockUpdated`).
3. Tuning migration indexes dựa trên query profile thực tế (Slow Query Log + EXPLAIN).

---

## Snippets mẫu triển khai nhanh

### A. Enum + Request + Model cast
```php
// app/Enums/PurchaseOrderStatus.php
enum PurchaseOrderStatus: string {
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

// Request
'status' => ['nullable', new Enum(PurchaseOrderStatus::class)]

// Model cast
'status' => PurchaseOrderStatus::class,
```

### B. Base scope for warehouse context
```php
trait BelongsToWarehouseScope
{
    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
```

### C. Event-driven stock update
```php
final class OrderCreated
{
    public function __construct(public Order $order) {}
}

final class UpdateStockAfterOrderCreated
{
    public function handle(OrderCreated $event): void
    {
        // stock adjust + inventory transaction
    }
}
```

### D. API Resource chuẩn hóa response
```php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->product_code,
            'name' => $this->name,
            'selling_price' => (float) $this->selling_price,
            'current_stock_quantity' => (int) ($this->current_stock_quantity ?? 0),
            'category' => CategoryResource::make($this->whenLoaded('category')),
        ];
    }
}
```

---

## Kết luận
Codebase hiện đã có nền tảng tốt về request validation, warehouse context, và một phần eager loading. Tuy nhiên, để đạt chuẩn backend Laravel enterprise-level, cần ưu tiên tách business logic ra service, chuẩn hóa enum/resource, tăng test coverage, bổ sung rate limiting, và chuyển dần sang event-driven architecture cho flow tồn kho.
