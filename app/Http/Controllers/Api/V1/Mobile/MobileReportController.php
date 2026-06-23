<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Report\StoreReportRequest;
use App\Http\Resources\Api\V1\ReportResource;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Support\ApiResponse;

class MobileReportController extends Controller
{
    public function __construct(private readonly ReportRepositoryInterface $reports)
    {
    }

    public function store(StoreReportRequest $request)
    {
        $report = $this->reports->create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $request->validated('reported_user_id'),
            'reason' => $request->validated('reason'),
            'description' => $request->validated('description'),
            'status' => ReportStatus::Pending->value,
        ]);

        return ApiResponse::success(new ReportResource($report), 'Report submitted.', 201);
    }
}
