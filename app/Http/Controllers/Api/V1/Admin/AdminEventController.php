<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Event\ReorderEventsRequest;
use App\Http\Requests\Api\V1\Admin\Event\StoreEventRequest;
use App\Http\Requests\Api\V1\Admin\Event\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Services\AdminLogService;
use App\Services\EventService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminEventController extends Controller
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly EventService $eventService,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->events->paginate(
            $request->only(['search']),
            (int) $request->get('per_page', 15),
        );

        return ApiResponse::paginated($paginator, EventResource::collection($paginator)->resolve());
    }

    public function store(StoreEventRequest $request)
    {
        $event = $this->eventService->create(
            $request->user(),
            $request->validated(),
            $request->file('photo'),
        );

        $this->adminLogs->log(
            $request->user(),
            'create_event',
            Event::class,
            $event->id,
            'Created event '.$event->title,
        );

        return ApiResponse::success(new EventResource($event->load('creator')), 'Event created.', 201);
    }

    public function show(Event $event)
    {
        return ApiResponse::success(new EventResource($event->load('creator')));
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $updated = $this->eventService->update(
            $event,
            $request->validated(),
            $request->file('photo'),
        );

        $this->adminLogs->log(
            $request->user(),
            'update_event',
            Event::class,
            $event->id,
            'Updated event '.$event->id,
        );

        return ApiResponse::success(new EventResource($updated->load('creator')), 'Event updated.');
    }

    public function reorder(ReorderEventsRequest $request)
    {
        $this->eventService->reorder($request->validated('ids'));

        $this->adminLogs->log(
            $request->user(),
            'reorder_events',
            Event::class,
            null,
            'Reordered events display order',
        );

        return ApiResponse::success(null, 'Event order updated.');
    }

    public function destroy(Event $event, Request $request)
    {
        $eventId = $event->id;
        $eventTitle = $event->title;
        $this->eventService->delete($event);

        $this->adminLogs->log(
            $request->user(),
            'delete_event',
            Event::class,
            $eventId,
            'Deleted event '.$eventTitle,
        );

        return ApiResponse::success(null, 'Event deleted.');
    }
}
