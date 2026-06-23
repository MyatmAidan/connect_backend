<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use App\Support\ApiResponse;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboard)
    {
    }

    public function stats()
    {
        return ApiResponse::success($this->dashboard->stats());
    }

    public function userGrowth()
    {
        return ApiResponse::success($this->dashboard->userGrowth());
    }

    public function activity()
    {
        return ApiResponse::success($this->dashboard->activity());
    }

    public function charts()
    {
        return ApiResponse::success($this->dashboard->charts());
    }
}
