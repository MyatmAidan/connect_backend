<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationLogResource;
use App\Models\NotificationLog;
use App\Repositories\Contracts\NotificationLogRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileNotificationController extends Controller
{
    public function __construct(private readonly NotificationLogRepositoryInterface $notifications)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->notifications->paginate(
            [
                'user_id' => $request->user()->id,
                'channel' => 'in_app',
            ],
            (int) $request->get('per_page', 20)
        );

        return ApiResponse::paginated($paginator, NotificationLogResource::collection($paginator)->resolve());
    }

    public function show(NotificationLog $notification, Request $request)
    {
        if ($notification->user_id !== $request->user()->id || $notification->channel !== 'in_app') {
            return ApiResponse::error('Notification not found.', 404);
        }

        return ApiResponse::success(new NotificationLogResource($notification));
    }

    public function unreadCount(Request $request)
    {
        $count = NotificationLog::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->count();

        return ApiResponse::success(['unread_count' => $count]);
    }

    public function markAsRead(NotificationLog $notification, Request $request)
    {
        if ($notification->user_id !== $request->user()->id || $notification->channel !== 'in_app') {
            return ApiResponse::error('Unauthorized.', 403);
        }

        $notification->update(['read_at' => now()]);

        return ApiResponse::success(new NotificationLogResource($notification), 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request)
    {
        $count = NotificationLog::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(['marked' => $count], 'All notifications marked as read.');
    }
}
