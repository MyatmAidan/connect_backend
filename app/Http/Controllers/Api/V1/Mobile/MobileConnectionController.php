<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ConnectionResource;
use App\Models\Connection;
use App\Repositories\Contracts\ConnectionRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileConnectionController extends Controller
{
    public function __construct(private readonly ConnectionRepositoryInterface $connections)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->connections->paginate(
            ['user_id' => $request->user()->id],
            (int) $request->get('per_page', 15)
        );

        return ApiResponse::paginated($paginator, ConnectionResource::collection($paginator)->resolve());
    }

    public function show(Connection $connection, Request $request)
    {
        $this->authorizeConnection($connection, $request);

        return ApiResponse::success(new ConnectionResource($connection->load(['userOne', 'userTwo', 'conversation'])));
    }

    public function destroy(Connection $connection, Request $request)
    {
        $this->authorizeConnection($connection, $request);
        $this->connections->delete($connection);

        return ApiResponse::success(null, 'Connection removed.');
    }

    private function authorizeConnection(Connection $connection, Request $request): void
    {
        $userId = $request->user()->id;
        if (! in_array($userId, [$connection->user_one_id, $connection->user_two_id], true)) {
            throw ValidationException::withMessages(['connection' => ['Unauthorized.']]);
        }
    }
}
