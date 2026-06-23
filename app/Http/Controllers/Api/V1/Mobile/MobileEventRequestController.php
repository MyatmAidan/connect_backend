<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Event\StoreEventRequestRequest;
use App\Http\Resources\Api\V1\EventRequestResource;
use App\Repositories\Contracts\EventRequestRepositoryInterface;
use App\Services\EventService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileEventRequestController extends Controller
{
    public function __construct(
        private readonly EventRequestRepositoryInterface $eventRequests,
        private readonly EventService $eventService,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->eventRequests->paginate(
            ['user_id' => $request->user()->id],
            (int) $request->get('per_page', 20),
        );

        return ApiResponse::paginated($paginator, EventRequestResource::collection($paginator)->resolve());
    }

    public function store(StoreEventRequestRequest $request)
    {
        $item = $this->eventService->submitRequest(
            $request->user(),
            $request->validated(),
            $request->file('photo'),
        );

        return ApiResponse::success(
            new EventRequestResource($item->load('user')),
            'Event request submitted. An admin will review it.',
            201,
        );
    }
}
