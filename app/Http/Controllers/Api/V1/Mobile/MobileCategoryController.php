<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileCategoryController extends Controller
{
    public function __construct(private readonly CategoryRepositoryInterface $categories)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->categories->paginate(
            $request->only(['search']),
            (int) $request->get('per_page', 50),
        );

        return ApiResponse::paginated(
            $paginator,
            CategoryResource::collection($paginator)->resolve(),
        );
    }
}
