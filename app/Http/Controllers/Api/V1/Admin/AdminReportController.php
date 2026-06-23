<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReportResource;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Services\AdminLogService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->reports->paginate($request->only(['status']), (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, ReportResource::collection($paginator)->resolve());
    }

    public function show(Report $report)
    {
        return ApiResponse::success(new ReportResource($report->load(['reporter', 'reportedUser', 'reviewer'])));
    }

    public function review(Report $report, Request $request)
    {
        $updated = $this->reports->update($report, [
            'status' => ReportStatus::Reviewed->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->adminLogs->log(
            $request->user(),
            'review_report',
            Report::class,
            $report->id,
            'Marked report '.$report->id.' as reviewed',
        );

        return ApiResponse::success(new ReportResource($updated), 'Report marked as reviewed.');
    }

    public function resolve(Report $report, Request $request)
    {
        $updated = $this->reports->update($report, [
            'status' => ReportStatus::Resolved->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->adminLogs->log(
            $request->user(),
            'resolve_report',
            Report::class,
            $report->id,
            'Resolved report '.$report->id,
        );

        return ApiResponse::success(new ReportResource($updated), 'Report resolved.');
    }

    public function reject(Report $report, Request $request)
    {
        $updated = $this->reports->update($report, [
            'status' => ReportStatus::Rejected->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->adminLogs->log(
            $request->user(),
            'reject_report',
            Report::class,
            $report->id,
            'Rejected report '.$report->id,
        );

        return ApiResponse::success(new ReportResource($updated), 'Report rejected.');
    }
}
