<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobResource;
use App\Models\Job;
use App\Services\AdminLogService;
use App\Services\JobService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminJobController extends Controller
{
    public function __construct(
        private readonly JobService $jobs,
        private readonly AdminLogService $adminLogs,
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->jobs->listAdmin(
            $request->only(['status', 'company_profile_id', 'search']),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, JobResource::collection($paginator)->resolve());
    }

    public function show(Job $job)
    {
        return ApiResponse::success(new JobResource($this->jobs->show($job)));
    }

    public function update(Request $request, Job $job)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['draft', 'open', 'closed', 'filled'])],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
        ]);

        $updated = $this->jobs->update($job, $data);

        $this->adminLogs->log(
            $request->user(),
            'update_job',
            Job::class,
            $job->id,
            'Updated job ' . $job->id,
        );

        return ApiResponse::success(new JobResource($updated), 'Job updated.');
    }

    public function destroy(Job $job, Request $request)
    {
        $jobId = $job->id;
        $this->jobs->delete($job);

        $this->adminLogs->log(
            $request->user(),
            'delete_job',
            Job::class,
            $jobId,
            'Deleted job ' . $jobId,
        );

        return ApiResponse::success(null, 'Job deleted.');
    }
}
