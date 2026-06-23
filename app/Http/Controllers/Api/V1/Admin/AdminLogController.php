<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdminLogResource;
use App\Models\AdminLog;
use App\Repositories\Contracts\AdminLogRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function __construct(private readonly AdminLogRepositoryInterface $logs)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->logs->paginate($request->only(['admin_id', 'action']), (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, AdminLogResource::collection($paginator)->resolve());
    }

    public function show(AdminLog $adminLog)
    {
        return ApiResponse::success(new AdminLogResource($adminLog->load('admin')));
    }
}
