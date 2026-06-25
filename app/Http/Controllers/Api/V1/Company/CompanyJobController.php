<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Enums\ExperienceLevel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobResource;
use App\Models\Company;
use App\Models\Job;
use App\Services\JobService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyJobController extends Controller
{
    public function __construct(private readonly JobService $jobs) {}

    public function index(Request $request)
    {
        /** @var Company $company */
        $company = $request->user()->companyProfile;

        if (! $company) {
            return ApiResponse::error('Company profile not found.', 404);
        }

        $paginator = $this->jobs->listForCompany(
            $company,
            $request->only(['status', 'search']),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, JobResource::collection($paginator)->resolve());
    }

    public function store(Request $request)
    {
        /** @var Company $c */
        $c = $request->user();
        $company = $c->companyProfile;

        if (! $company) {
            return ApiResponse::error('Company profile not found.', 404);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'category_id' => ['nullable', 'ulid', 'exists:categories,id'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'internship', 'remote'])],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'closes_at' => ['nullable', 'date', 'after:now'],
        ]);

        $job = $this->jobs->create($company, $data);

        return ApiResponse::success(new JobResource($job), 'Job created.', 201);
    }

    public function show(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);

        return ApiResponse::success(new JobResource($this->jobs->show($job)));
    }

    public function update(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'requirements' => ['nullable', 'string'],
            'category_id' => ['nullable', 'ulid', 'exists:categories,id'],
            'employment_type' => ['sometimes', Rule::in(['full_time', 'part_time', 'contract', 'internship', 'remote'])],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'closes_at' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($job): void {
                    if (! $value) {
                        return;
                    }

                    $incoming = \Carbon\Carbon::parse($value);
                    if (! $incoming->isPast()) {
                        return;
                    }

                    $existing = $job->closes_at?->format('Y-m-d H:i:s');
                    if ($existing !== $incoming->format('Y-m-d H:i:s')) {
                        $fail('The application deadline must be in the future.');
                    }
                },
            ],
        ]);

        $updated = $this->jobs->update($job, $data);

        return ApiResponse::success(new JobResource($updated), 'Job updated.');
    }

    public function publish(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);

        return ApiResponse::success(new JobResource($this->jobs->publish($job)), 'Job published.');
    }

    public function close(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);

        return ApiResponse::success(new JobResource($this->jobs->close($job)), 'Job closed.');
    }

    public function reopen(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);

        return ApiResponse::success(new JobResource($this->jobs->reopen($job)), 'Job reopened.');
    }

    public function destroy(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);
        $this->jobs->delete($job);

        return ApiResponse::success(null, 'Job deleted.');
    }

    private function authorizeJob(Request $request, Job $job): void
    {
        /** @var Company $c */
        $c = $request->user();
        $company = $c->companyProfile;

        if (! $company || $job->company_profile_id !== $company->id) {
            abort(response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404));
        }
    }
}
