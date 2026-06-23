<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyProfileResource;
use App\Models\CompanyProfile;
use App\Services\AdminLogService;
use App\Services\CompanyProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    public function __construct(
        private readonly CompanyProfileService $companies,
        private readonly AdminLogService $adminLogs,
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->companies->listAdmin(
            $request->only(['search', 'is_verified', 'is_active']),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, CompanyProfileResource::collection($paginator)->resolve());
    }

    public function show(CompanyProfile $companyProfile)
    {
        return ApiResponse::success(new CompanyProfileResource($companyProfile->load('company')));
    }

    public function update(Request $request, CompanyProfile $companyProfile)
    {
        $data = $request->validate([
            'company_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'is_verified' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updated = $this->companies->adminUpdate($companyProfile, $data);

        $this->adminLogs->log(
            $request->user(),
            'update_company_profile',
            CompanyProfile::class,
            $companyProfile->id,
            'Updated company profile ' . $companyProfile->id,
        );

        return ApiResponse::success(new CompanyProfileResource($updated), 'Company updated.');
    }
}
