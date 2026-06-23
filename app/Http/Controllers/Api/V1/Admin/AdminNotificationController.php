<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Notification\BroadcastNotificationRequest;
use App\Http\Resources\Api\V1\NotificationLogResource;
use App\Models\NotificationLog;
use App\Models\User;
use App\Repositories\Contracts\NotificationLogRepositoryInterface;
use App\Services\AdminLogService;
use App\Services\TelegramNotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationLogRepositoryInterface $notifications,
        private readonly TelegramNotificationService $telegramNotifications,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->notifications->paginate($request->only(['channel', 'status']), (int) $request->get('per_page', 15));

        return ApiResponse::paginated($paginator, NotificationLogResource::collection($paginator)->resolve());
    }

    public function broadcast(BroadcastNotificationRequest $request)
    {
        $data = $request->validated();
        $userIds = $data['user_ids'] ?? null;

        if ($data['channel'] === 'telegram') {
            if (! $this->telegramNotifications->isConfigured()) {
                return ApiResponse::error('Telegram bot token is not configured.', 503);
            }

            $result = $this->telegramNotifications->broadcast(
                $data['title'],
                $data['body'],
                $userIds,
            );

            $this->logBroadcast($request, 'telegram', $data['title'], $result['recipients']);

            return ApiResponse::success($result, 'Telegram broadcast completed.');
        }

        $users = $this->recipientIds($userIds);

        foreach ($users as $userId) {
            NotificationLog::query()->create([
                'user_id' => $userId,
                'channel' => $data['channel'],
                'type' => 'system_broadcast',
                'title' => $data['title'],
                'body' => $data['body'],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        $this->logBroadcast($request, $data['channel'], $data['title'], $users->count());

        return ApiResponse::success(['recipients' => $users->count()], 'Broadcast sent.');
    }

    private function logBroadcast(Request $request, string $channel, string $title, int $recipients): void
    {
        $audience = $request->validated('user_ids')
            ? 'selected users'
            : 'all active users';

        $this->adminLogs->log(
            $request->user(),
            'broadcast_notification',
            null,
            null,
            "Sent {$channel} notification \"{$title}\" to {$recipients} {$audience}",
        );
    }

    /**
     * @param  array<string>|null  $userIds
     */
    private function recipientIds(?array $userIds)
    {
        $query = User::query()->where('status', 'active');

        if ($userIds) {
            $query->whereIn('id', $userIds);
        }

        return $query->pluck('id');
    }
}
