<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventRegistrationResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Services\AdminLogService;
use App\Services\EventRegistrationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminEventRegistrationController extends Controller
{
    public function __construct(
        private readonly EventRegistrationRepositoryInterface $registrations,
        private readonly EventRegistrationService $registrationService,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Event $event, Request $request)
    {
        $paginator = $this->registrations->paginate(
            array_merge($request->only(['status']), ['event_id' => $event->id]),
            (int) $request->get('per_page', 20),
        );

        return ApiResponse::paginated($paginator, EventRegistrationResource::collection($paginator)->resolve());
    }

    public function accept(Event $event, EventRegistration $eventRegistration, Request $request)
    {
        $this->assertRegistrationBelongsToEvent($event, $eventRegistration);

        $updated = $this->registrationService->accept($eventRegistration, $request->user());

        $this->adminLogs->log(
            $request->user(),
            'accept_event_registration',
            EventRegistration::class,
            $eventRegistration->id,
            'Accepted event registration '.$eventRegistration->id,
        );

        return ApiResponse::success(
            new EventRegistrationResource($updated),
            'Event registration accepted.',
        );
    }

    public function reject(Event $event, EventRegistration $eventRegistration, Request $request)
    {
        $this->assertRegistrationBelongsToEvent($event, $eventRegistration);

        $updated = $this->registrationService->reject($eventRegistration, $request->user());

        $this->adminLogs->log(
            $request->user(),
            'reject_event_registration',
            EventRegistration::class,
            $eventRegistration->id,
            'Rejected event registration '.$eventRegistration->id,
        );

        return ApiResponse::success(
            new EventRegistrationResource($updated),
            'Event registration rejected.',
        );
    }

    private function assertRegistrationBelongsToEvent(Event $event, EventRegistration $registration): void
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }
    }
}
