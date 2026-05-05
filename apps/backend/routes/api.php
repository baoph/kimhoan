<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');

    // Các route chỉ cần đăng nhập, không cần warehouse context.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/profile', [AuthController::class, 'profile']);
        Route::get('/auth/user', [AuthController::class, 'profile']);
        Route::get('/user', [AuthController::class, 'profile']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Frontend cần gọi endpoint này ngay sau login để lấy danh sách kho có quyền.
        Route::get('/warehouses', [WarehouseController::class, 'index']);

        // Chỉ admin được quản lý tài khoản người dùng.
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
            Route::post('/users/{user}/lock', [UserController::class, 'lock']);
            Route::post('/users/{user}/unlock', [UserController::class, 'unlock']);
            Route::post('/users/{user}/assign-warehouses', [UserController::class, 'assignWarehouses']);
            Route::get('/users/{user}/activity-logs', [UserController::class, 'activityLogs']);
        });
    });

    // Các route nghiệp vụ bắt buộc có X-Warehouse-Id.
    Route::middleware(['auth:sanctum', 'check.warehouse.access'])->group(function () {
        Route::apiResource('customers', CustomerController::class);

        Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
        Route::apiResource('products', ProductController::class);

        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::apiResource('orders', OrderController::class);

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('brands', BrandController::class);

        Route::get('/warehouses/{warehouse}/stock', [WarehouseController::class, 'stock']);
        Route::apiResource('warehouses', WarehouseController::class)->except(['index']);

        Route::get('/suppliers/{supplier}/purchase-history', [SupplierController::class, 'purchaseHistory']);
        Route::apiResource('suppliers', SupplierController::class);

        Route::post('/purchase-orders/{purchaseOrder}/complete', [PurchaseOrderController::class, 'complete']);
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
        Route::apiResource('purchase-orders', PurchaseOrderController::class)
            ->parameters(['purchase-orders' => 'purchaseOrder']);

        Route::prefix('dashboard')->group(function () {
            Route::get('/today-stats', [DashboardController::class, 'getTodayStats']);
            Route::get('/top-selling-products', [DashboardController::class, 'getTopSellingProducts']);
            Route::get('/top-customers', [DashboardController::class, 'getTopCustomers']);
            Route::get('/revenue-chart', [DashboardController::class, 'getRevenueChart']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('/sales', [ReportController::class, 'salesReport']);
            Route::get('/inventory', [ReportController::class, 'inventoryReport']);
        });
    });
});
