<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        return $this->successResponse(Brand::latest()->get(), 'Lấy danh sách thương hiệu thành công');
    }

    public function store(StoreBrandRequest $request)
    {
        $brand = Brand::create($request->validated());

        return $this->successResponse($brand, 'Tạo thương hiệu thành công', 201);
    }

    public function show(Brand $brand)
    {
        return $this->successResponse($brand->load('products'), 'Lấy chi tiết thương hiệu thành công');
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $brand->update($request->validated());

        return $this->successResponse($brand, 'Cập nhật thương hiệu thành công');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return $this->successResponse(null, 'Xóa thương hiệu thành công');
    }
}
