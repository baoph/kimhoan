<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['category', 'brand']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($request->boolean('low_stock_only')) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $products = $query->latest()->paginate($perPage);

        return $this->paginatedResponse($products, 'Lấy danh sách sản phẩm thành công');
    }

    public function lowStock(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $products = Product::query()
            ->with(['category', 'brand'])
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->paginate($perPage);

        return $this->paginatedResponse($products, 'Lấy danh sách hàng sắp hết thành công');
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return $this->successResponse(
            $product->load(['category', 'brand']),
            'Tạo sản phẩm thành công',
            201
        );
    }

    public function show(Product $product)
    {
        return $this->successResponse(
            $product->load(['category', 'brand', 'orderItems']),
            'Lấy chi tiết sản phẩm thành công'
        );
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return $this->successResponse(
            $product->load(['category', 'brand']),
            'Cập nhật sản phẩm thành công'
        );
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->successResponse(null, 'Xóa sản phẩm thành công');
    }
}
