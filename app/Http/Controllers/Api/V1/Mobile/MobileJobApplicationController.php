<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobApplicationResource;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\JobApplicationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileJobApplicationController extends Controller
{
    public function __construct(private readonly JobApplicationService $applications)
    {
    }

    public function apply(Request $request, Job $job)
    {
        $data = $request->validate([
            'cover_letter' => ['nullable', 'string'],
        ]);

        $application = $this->applications->apply(
            $request->user(),
            $job,
            $data['cover_letter'] ?? null,
        );

        return ApiResponse::success(
            new JobApplicationResource($this->applications->show($application)),
            'Application submitted.',
            201,
        );
    }

    public function myApplications(Request $request)
    {
        $paginator = $this->applications->myApplications(
            $request->user(),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, JobApplicationResource::collection($paginator)->resolve());
    }

    public function withdraw(Request $request, JobApplication $jobApplication)
    {
        if ($jobApplication->applicant_id !== $request->user()->id) {
            return ApiResponse::error('Application not found.', 404);
        }

        $updated = $this->applications->withdraw($request->user(), $jobApplication);

        return ApiResponse::success(new JobApplicationResource($updated), 'Application withdrawn.');
    }
}
