<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobApplicationResource;
use App\Models\JobApplication;
use App\Services\JobApplicationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminJobApplicationController extends Controller
{
    public function __construct(private readonly JobApplicationService $applications)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->applications->listAdmin(
            $request->only(['status', 'job_id', 'company_profile_id']),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, JobApplicationResource::collection($paginator)->resolve());
    }

    public function show(JobApplication $jobApplication)
    {
        return ApiResponse::success(new JobApplicationResource($this->applications->show($jobApplication)));
    }
}
