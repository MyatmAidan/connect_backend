<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Connection\StoreConnectionRequestRequest;
use App\Http\Resources\Api\V1\ConnectionRequestResource;
use App\Http\Resources\Api\V1\ConnectionResource;
use App\Models\ConnectionRequest;
use App\Services\ConnectionRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileConnectionRequestController extends Controller
{
    public function __construct(private readonly ConnectionRequestService $service)
    {
    }

    public function store(StoreConnectionRequestRequest $request)
    {
        $item = $this->service->store($request->user(), $request->validated());

        return ApiResponse::success(new ConnectionRequestResource($item), 'Request sent.', 201);
    }

    public function received(Request $request)
    {
        $paginator = $this->service->list([
            'receiver_id' => $request->user()->id,
            'status' => $request->get('status'),
        ], (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, ConnectionRequestResource::collection($paginator)->resolve());
    }

    public function sent(Request $request)
    {
        $paginator = $this->service->list([
            'sender_id' => $request->user()->id,
            'status' => $request->get('status'),
        ], (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, ConnectionRequestResource::collection($paginator)->resolve());
    }

    public function accept(ConnectionRequest $connectionRequest, Request $request)
    {
        $connection = $this->service->accept($connectionRequest, $request->user());

        return ApiResponse::success(
            new ConnectionResource($connection->load(['userOne', 'userTwo', 'conversation'])),
            'Friend request accepted.',
        );
    }

    public function reject(ConnectionRequest $connectionRequest, Request $request)
    {
        $item = $this->service->reject($connectionRequest, $request->user());

        return ApiResponse::success(new ConnectionRequestResource($item), 'Request rejected.');
    }

    public function cancel(ConnectionRequest $connectionRequest, Request $request)
    {
        $item = $this->service->cancel($connectionRequest, $request->user());

        return ApiResponse::success(new ConnectionRequestResource($item), 'Request cancelled.');
    }
}
