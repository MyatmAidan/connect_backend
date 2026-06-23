<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ApiResponse;

class CompanyCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()->orderBy('name_en')->get(['id', 'slug', 'name_en', 'name_my']);

        return ApiResponse::success($categories->toArray());
    }
}
