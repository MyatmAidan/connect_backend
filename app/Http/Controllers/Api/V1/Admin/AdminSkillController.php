<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Skill\StoreSkillRequest;
use App\Http\Requests\Api\V1\Admin\Skill\UpdateSkillRequest;
use App\Http\Resources\Api\V1\SkillResource;
use App\Models\Skill;
use App\Repositories\Contracts\SkillRepositoryInterface;
use App\Services\AdminLogService;
use App\Services\SkillService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminSkillController extends Controller
{
    public function __construct(
        private readonly SkillRepositoryInterface $skills,
        private readonly SkillService $skillService,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->skills->paginate($request->only(['search', 'category_id']), (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, SkillResource::collection($paginator)->resolve());
    }

    public function store(StoreSkillRequest $request)
    {
        $skill = $this->skillService->create(
            $request->validated(),
            $request->file('image'),
        );

        $this->adminLogs->log(
            $request->user(),
            'create_skill',
            Skill::class,
            $skill->id,
            'Created skill '.$skill->name,
        );

        return ApiResponse::success(new SkillResource($skill->load('category')), 'Skill created.', 201);
    }

    public function update(UpdateSkillRequest $request, Skill $skill)
    {
        $updated = $this->skillService->update(
            $skill,
            $request->validated(),
            $request->file('image'),
        );

        $this->adminLogs->log(
            $request->user(),
            'update_skill',
            Skill::class,
            $skill->id,
            'Updated skill '.$skill->id,
        );

        return ApiResponse::success(new SkillResource($updated->load('category')), 'Skill updated.');
    }

    public function destroy(Skill $skill, Request $request)
    {
        $skillId = $skill->id;
        $skillName = $skill->name;
        $this->skillService->delete($skill);

        $this->adminLogs->log(
            $request->user(),
            'delete_skill',
            Skill::class,
            $skillId,
            'Deleted skill '.$skillName,
        );

        return ApiResponse::success(null, 'Skill deleted.');
    }
}
