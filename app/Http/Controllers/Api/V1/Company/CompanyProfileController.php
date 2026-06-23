<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyProfileResource;
use App\Models\Company;
use App\Services\CompanyProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    public function __construct(private readonly CompanyProfileService $profiles) {}

    public function show(Request $request)
    {
        /** @var Company $company */
        $company = $request->user();
        $profile = $this->profiles->showForCompany($company);

        if (! $profile) {
            return ApiResponse::error('Company profile not found.', 404);
        }

        return ApiResponse::success(new CompanyProfileResource($profile));
    }

    public function update(Request $request)
    {
        /** @var Company $company */
        $company = $request->user();
        $profile = $company->companyProfile;

        if (! $profile) {
            return ApiResponse::error('Company profile not found.', 404);
        }

        $data = $request->validate([
            'company_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $updated = $this->profiles->update($profile, $data);

        return ApiResponse::success(new CompanyProfileResource($updated), 'Company profile updated.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        /** @var Company $company */
        $company = $request->user();
        $url = $this->profiles->uploadLogo($company, $request->file('logo'));

        return ApiResponse::success(['logo' => $url], 'Logo uploaded.');
    }
}
