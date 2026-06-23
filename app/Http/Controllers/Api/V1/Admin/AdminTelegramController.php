<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminTelegramController extends Controller
{
    public function stats()
    {
        return ApiResponse::success([
            'connected_users' => User::query()->whereNotNull('telegram_chat_id')->count(),
            'notify_enabled_users' => User::query()->where('telegram_notify_enabled', true)->count(),
            'failed_notifications' => NotificationLog::query()
                ->where('channel', 'telegram')
                ->where('status', 'failed')
                ->count(),
        ]);
    }

    public function logs(Request $request)
    {
        $logs = NotificationLog::query()
            ->where('channel', 'telegram')
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return ApiResponse::paginated($logs, $logs->items());
    }
}
