<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AdminUserService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $users)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->users->list(
            $request->only(['search', 'status', 'role']),
            (int) $request->get('per_page', 15)
        );

        return ApiResponse::paginated($paginator, UserResource::collection($paginator)->resolve());
    }

    public function show(User $user)
    {
        return ApiResponse::success(new UserResource($user->load([
            'developerProfile.skills',
            'developerProfile.category',
        ])));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $updated = $this->users->update($user, $request->validated(), $request->user());

        return ApiResponse::success(new UserResource($updated), 'User updated.');
    }

    public function ban(User $user, Request $request)
    {
        $updated = $this->users->ban($user, $request->user());

        return ApiResponse::success(new UserResource($updated), 'User banned.');
    }

    public function unban(User $user, Request $request)
    {
        $updated = $this->users->unban($user, $request->user());

        return ApiResponse::success(new UserResource($updated), 'User unbanned.');
    }

    public function destroy(User $user, Request $request)
    {
        $this->users->delete($user, $request->user());

        return ApiResponse::success(null, 'User deleted.');
    }
}
