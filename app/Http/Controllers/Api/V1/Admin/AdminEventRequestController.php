<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EventRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventRequestResource;
use App\Models\EventRequest;
use App\Repositories\Contracts\EventRequestRepositoryInterface;
use App\Services\AdminLogService;
use App\Services\EventService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminEventRequestController extends Controller
{
    public function __construct(
        private readonly EventRequestRepositoryInterface $eventRequests,
        private readonly EventService $eventService,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->eventRequests->paginate(
            $request->only(['status']),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, EventRequestResource::collection($paginator)->resolve());
    }

    public function show(EventRequest $eventRequest)
    {
        return ApiResponse::success(new EventRequestResource(
            $eventRequest->load(['user', 'reviewer', 'event']),
        ));
    }

    public function approve(EventRequest $eventRequest, Request $request)
    {
        if ($eventRequest->status !== EventRequestStatus::Pending) {
            throw ValidationException::withMessages(['event_request' => ['Only pending requests can be approved.']]);
        }

        $updated = $this->eventService->approveRequest($eventRequest, $request->user());

        $this->adminLogs->log(
            $request->user(),
            'approve_event_request',
            EventRequest::class,
            $eventRequest->id,
            'Approved event request '.$eventRequest->id,
        );

        return ApiResponse::success(
            new EventRequestResource($updated->load(['user', 'reviewer', 'event'])),
            'Event request approved and event created.',
        );
    }

    public function reject(EventRequest $eventRequest, Request $request)
    {
        if ($eventRequest->status !== EventRequestStatus::Pending) {
            throw ValidationException::withMessages(['event_request' => ['Only pending requests can be rejected.']]);
        }

        $updated = $this->eventService->rejectRequest($eventRequest, $request->user());

        $this->adminLogs->log(
            $request->user(),
            'reject_event_request',
            EventRequest::class,
            $eventRequest->id,
            'Rejected event request '.$eventRequest->id,
        );

        return ApiResponse::success(
            new EventRequestResource($updated->load(['user', 'reviewer'])),
            'Event request rejected.',
        );
    }
}
