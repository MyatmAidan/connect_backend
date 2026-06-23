<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\BlockedUser;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileBlockController extends Controller
{
    public function block(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return ApiResponse::error('You cannot block yourself.', 422);
        }

        BlockedUser::query()->firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $user->id,
        ]);

        return ApiResponse::success(null, 'User blocked.');
    }

    public function unblock(User $user, Request $request)
    {
        BlockedUser::query()
            ->where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        return ApiResponse::success(null, 'User unblocked.');
    }

    public function index(Request $request)
    {
        $blocked = BlockedUser::query()
            ->where('blocker_id', $request->user()->id)
            ->with('blocked')
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return ApiResponse::paginated(
            $blocked,
            UserResource::collection($blocked->pluck('blocked'))->resolve()
        );
    }
}
