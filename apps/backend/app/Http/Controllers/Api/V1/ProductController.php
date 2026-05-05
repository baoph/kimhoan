<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();

        $query = Product::query()->with([
            'category',
            'brand',
            'warehouseStocks' => fn ($q) => $q->where('warehouse_id', $warehouseId),
        ]);

        if ($search = $request->string('search')->toString()) {
            $query->search($search);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->byCategory((int) $categoryId);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($request->boolean('low_stock_only')) {
            $query->whereHas('warehouseStocks', function ($stockQuery) use ($warehouseId) {
                $stockQuery->where('warehouse_id', $warehouseId)
                    ->whereColumn('quantity', '<=', 'products.min_stock');
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $products = $query->latest()->paginate($perPage);
        $this->appendWarehouseStockContext($products);
        $products->setCollection(collect(ProductResource::collection($products->getCollection())->resolve()));

        return $this->paginatedResponse($products, 'Lấy danh sách sản phẩm thành công');
    }

    public function lowStock(Request $request)
    {
        $warehouseId = (int) getCurrentWarehouseId();
        $perPage = min((int) $request->input('per_page', 15), 100);

        $products = Product::query()
            ->with([
                'category',
                'brand',
                'warehouseStocks' => fn ($q) => $q->where('warehouse_id', $warehouseId),
            ])
            ->whereHas('warehouseStocks', function ($stockQuery) use ($warehouseId) {
                $stockQuery->where('warehouse_id', $warehouseId)
                    ->whereColumn('quantity', '<=', 'products.min_stock');
            })
            ->paginate($perPage);

        $this->appendWarehouseStockContext($products);
        $products->setCollection(collect(ProductResource::collection($products->getCollection())->resolve()));

        return $this->paginatedResponse($products, 'Lấy danh sách hàng sắp hết thành công');
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->createProduct($request->validated(), (int) getCurrentWarehouseId());

            return $this->successResponse(
                (new ProductResource($product->load(['category', 'brand', 'warehouseStocks'])))->resolve(),
                'Tạo sản phẩm thành công',
                201
            );
        } catch (Throwable $exception) {
            return $this->errorResponse('Không thể tạo sản phẩm', ['error' => $exception->getMessage()], 500);
        }
    }

    public function show(Product $product)
    {
        $warehouseId = (int) getCurrentWarehouseId();

        $product->load([
            'category',
            'brand',
            'orderItems',
            'warehouseStocks' => fn ($q) => $q->where('warehouse_id', $warehouseId)->with('warehouse'),
        ]);

        $stock = $product->warehouseStocks->first();
        $product->setAttribute('current_stock_quantity', (int) ($stock->quantity ?? 0));

        return $this->successResponse((new ProductResource($product))->resolve(), 'Lấy chi tiết sản phẩm thành công');
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return $this->successResponse(
            (new ProductResource($product->fresh()->load(['category', 'brand'])))->resolve(),
            'Cập nhật sản phẩm thành công'
        );
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->successResponse(null, 'Xóa sản phẩm thành công');
    }

    private function appendWarehouseStockContext($paginatedProducts): void
    {
        $paginatedProducts->getCollection()->transform(function (Product $product) {
            $stock = $product->warehouseStocks->first();
            $product->setAttribute('current_stock_quantity', (int) ($stock->quantity ?? 0));

            return $product;
        });
    }
}
