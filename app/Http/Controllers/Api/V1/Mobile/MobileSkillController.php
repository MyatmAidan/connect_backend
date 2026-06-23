<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SkillResource;
use App\Repositories\Contracts\SkillRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileSkillController extends Controller
{
    public function __construct(private readonly SkillRepositoryInterface $skills)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->skills->paginate(
            $request->only(['search', 'category_id']),
            (int) $request->get('per_page', 50)
        );

        return ApiResponse::paginated(
            $paginator,
            SkillResource::collection($paginator)->resolve()
        );
    }
}
