<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobApplicationResource;
use App\Models\Company;
use App\Models\JobApplication;
use App\Services\JobApplicationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyJobApplicationController extends Controller
{
    public function __construct(private readonly JobApplicationService $applications)
    {
    }

    public function index(Request $request)
    {
        /** @var Company $c */
        $c = $request->user();
        $company = $c->companyProfile;

        if (! $company) {
            return ApiResponse::error('Company profile not found.', 404);
        }

        $paginator = $this->applications->listForCompany(
            $company,
            $request->only(['status', 'job_id']),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, JobApplicationResource::collection($paginator)->resolve());
    }

    public function show(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($request, $jobApplication);

        return ApiResponse::success(new JobApplicationResource($this->applications->show($jobApplication)));
    }

    public function updateStatus(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($request, $jobApplication);

        $data = $request->validate([
            'status' => ['required', Rule::in(['reviewed', 'shortlisted', 'accepted', 'rejected'])],
            'company_notes' => ['nullable', 'string'],
        ]);

        $updated = $this->applications->updateStatus(
            $jobApplication,
            $data['status'],
            $data['company_notes'] ?? null,
        );

        return ApiResponse::success(new JobApplicationResource($updated), 'Application status updated.');
    }

    public function sendInterviewAcknowledgment(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($request, $jobApplication);

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->applications->sendInterviewAcknowledgment(
            $jobApplication,
            $data['message'] ?? null,
        );

        return ApiResponse::success(new JobApplicationResource($updated), 'Interview acknowledgment sent via Telegram.');
    }

    private function authorizeApplication(Request $request, JobApplication $application): void
    {
        /** @var Company $c */
        $c = $request->user();
        $company = $c->companyProfile;

        if (! $company || $application->job?->company_profile_id !== $company->id) {
            abort(response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], 404));
        }
    }
}
