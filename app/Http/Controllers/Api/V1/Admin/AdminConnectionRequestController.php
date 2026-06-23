<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ConnectionRequestResource;
use App\Repositories\Contracts\ConnectionRequestRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminConnectionRequestController extends Controller
{
    public function __construct(private readonly ConnectionRequestRepositoryInterface $requests)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->requests->paginate($request->only(['status']), (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, ConnectionRequestResource::collection($paginator)->resolve());
    }
}
