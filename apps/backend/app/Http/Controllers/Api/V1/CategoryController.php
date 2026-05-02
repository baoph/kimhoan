<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['children'])->whereNull('parent_id')->get();

        return $this->successResponse($categories, 'Lấy danh sách nhóm hàng thành công');
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return $this->successResponse($category, 'Tạo nhóm hàng thành công', 201);
    }

    public function show(Category $category)
    {
        return $this->successResponse($category->load(['parent', 'children']), 'Lấy chi tiết nhóm hàng thành công');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        if (isset($data['parent_id']) && (int) $data['parent_id'] === (int) $category->id) {
            return $this->errorResponse('Nhóm hàng cha không hợp lệ', null, 422);
        }

        $category->update($data);

        return $this->successResponse($category, 'Cập nhật nhóm hàng thành công');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return $this->successResponse(null, 'Xóa nhóm hàng thành công');
    }
}
