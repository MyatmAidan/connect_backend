<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\AdminLogService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->categories->paginate(
            $request->only(['search']),
            (int) $request->get('per_page', 50),
        );

        return ApiResponse::paginated($paginator, CategoryResource::collection($paginator)->resolve());
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categories->create([
            'name_en' => $request->validated('name_en'),
            'name_my' => $request->validated('name_my'),
            'slug' => Str::slug($request->validated('name_en')),
        ]);

        $this->adminLogs->log(
            $request->user(),
            'create_category',
            Category::class,
            $category->id,
            'Created category '.$category->name_en,
        );

        return ApiResponse::success(new CategoryResource($category), 'Category created.', 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $updated = $this->categories->update($category, [
            'name_en' => $request->validated('name_en'),
            'name_my' => $request->validated('name_my'),
            'slug' => Str::slug($request->validated('name_en')),
        ]);

        $this->adminLogs->log(
            $request->user(),
            'update_category',
            Category::class,
            $category->id,
            'Updated category '.$category->id,
        );

        return ApiResponse::success(new CategoryResource($updated), 'Category updated.');
    }

    public function destroy(Category $category, Request $request)
    {
        $categoryId = $category->id;
        $categoryName = $category->name_en;
        $this->categories->delete($category);

        $this->adminLogs->log(
            $request->user(),
            'delete_category',
            Category::class,
            $categoryId,
            'Deleted category '.$categoryName,
        );

        return ApiResponse::success(null, 'Category deleted.');
    }
}
