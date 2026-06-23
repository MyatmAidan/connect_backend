<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\TelegramLinkToken;
use App\Services\TelegramNotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileTelegramController extends Controller
{
    public function __construct(private readonly TelegramNotificationService $telegramNotifications)
    {
    }
    public function createLinkToken(Request $request)
    {
        $token = TelegramLinkToken::query()->create([
            'user_id' => $request->user()->id,
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes(30),
        ]);

        return ApiResponse::success([
            'token' => $token->token,
            'expires_at' => $token->expires_at,
        ], 'Link token created.');
    }

    public function sendTest(Request $request)
    {
        if (! $request->user()->telegram_chat_id) {
            return ApiResponse::error('Telegram is not connected.', 422);
        }

        if (! $this->telegramNotifications->isConfigured()) {
            return ApiResponse::error('Telegram bot token is not configured.', 503);
        }

        $log = $this->telegramNotifications->sendTest($request->user());

        if ($log->status === 'failed') {
            return ApiResponse::error($log->error_message ?? 'Failed to send Telegram notification.', 502);
        }

        return ApiResponse::success(null, 'Test notification sent.');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'telegram_notify_enabled' => ['required', 'boolean'],
        ]);

        $request->user()->update($data);

        return ApiResponse::success(null, 'Telegram settings updated.');
    }

    public function disconnect(Request $request)
    {
        $request->user()->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_notify_enabled' => false,
            'telegram_linked_at' => null,
        ]);

        return ApiResponse::success(null, 'Telegram disconnected.');
    }
}
