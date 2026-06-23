<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Event\StoreEventRegistrationRequest;
use App\Http\Resources\Api\V1\EventRegistrationResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Services\EventRegistrationService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class MobileEventRegistrationController extends Controller
{
    public function __construct(
        private readonly EventRegistrationRepositoryInterface $registrations,
        private readonly EventRegistrationService $registrationService,
    ) {
    }

    public function index(Event $event, Request $request)
    {
        if (! $this->registrationService->canManage($request->user(), $event)) {
            throw new AuthorizationException('You are not allowed to view event registrations.');
        }

        $paginator = $this->registrations->paginate(
            array_merge($request->only(['status']), ['event_id' => $event->id]),
            (int) $request->get('per_page', 20),
        );

        return ApiResponse::paginated($paginator, EventRegistrationResource::collection($paginator)->resolve());
    }

    public function store(Event $event, StoreEventRegistrationRequest $request)
    {
        $registration = $this->registrationService->register(
            $request->user(),
            $event,
            $request->validated(),
        );

        return ApiResponse::success(
            new EventRegistrationResource($registration),
            'Event registration submitted.',
            201,
        );
    }

    public function accept(Event $event, EventRegistration $eventRegistration, Request $request)
    {
        $this->assertRegistrationBelongsToEvent($event, $eventRegistration);

        $updated = $this->registrationService->accept($eventRegistration, $request->user());

        return ApiResponse::success(
            new EventRegistrationResource($updated),
            'Event registration accepted.',
        );
    }

    public function reject(Event $event, EventRegistration $eventRegistration, Request $request)
    {
        $this->assertRegistrationBelongsToEvent($event, $eventRegistration);

        $updated = $this->registrationService->reject($eventRegistration, $request->user());

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
