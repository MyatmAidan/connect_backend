<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyDashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CompanyDashboardController extends Controller
{
    public function __construct(private readonly CompanyDashboardService $dashboard)
    {
    }

    public function stats(Request $request)
    {
        /** @var Company $companyUser */
        $companyUser = $request->user();
        $company = $companyUser->companyProfile;

        if (! $company) {
            return ApiResponse::error('Company profile not found.', 404);
        }

        return ApiResponse::success($this->dashboard->stats($company));
    }
}
