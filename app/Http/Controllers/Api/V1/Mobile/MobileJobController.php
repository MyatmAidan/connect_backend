<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobResource;
use App\Models\Job;
use App\Services\JobService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileJobController extends Controller
{
    public function __construct(private readonly JobService $jobs)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->jobs->listPublic(
            $request->only(['search', 'employment_type', 'experience_level', 'category_id']),
            (int) $request->get('per_page', 15),
            $request->user(),
        );

        return ApiResponse::paginated($paginator, JobResource::collection($paginator)->resolve());
    }

    public function show(Request $request, Job $job)
    {
        $publicJob = $this->jobs->showPublic($job->id, $request->user());

        if (! $publicJob) {
            return ApiResponse::error('Job not found.', 404);
        }

        return ApiResponse::success(new JobResource($publicJob));
    }
}
