<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ConnectionResource;
use App\Models\Connection;
use App\Repositories\Contracts\ConnectionRepositoryInterface;
use App\Services\AdminLogService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionRepositoryInterface $connections,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->connections->paginate([], (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, ConnectionResource::collection($paginator)->resolve());
    }

    public function show(Connection $connection)
    {
        return ApiResponse::success(new ConnectionResource($connection->load(['userOne', 'userTwo', 'conversation'])));
    }

    public function destroy(Connection $connection, Request $request)
    {
        $connectionId = $connection->id;
        $this->connections->delete($connection);

        $this->adminLogs->log(
            $request->user(),
            'delete_connection',
            Connection::class,
            $connectionId,
            'Deleted connection '.$connectionId,
        );

        return ApiResponse::success(null, 'Connection deleted.');
    }
}
