<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileEventController extends Controller
{
    public function __construct(private readonly EventRepositoryInterface $events)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->events->paginate(
            $request->only(['search']),
            (int) $request->get('per_page', 20),
        );

        return ApiResponse::paginated($paginator, EventResource::collection($paginator)->resolve());
    }

    public function show(Event $event, Request $request)
    {
        return ApiResponse::success(new EventResource(
            $event->load('creator')->loadCount('registrations'),
        ));
    }
}
