<?php

namespace App\Http\Controllers\Api\V1\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramUpdateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(private readonly TelegramUpdateService $telegramUpdates)
    {
    }

    public function handle(Request $request): Response
    {
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            Log::warning('Telegram webhook rejected: invalid secret token');

            return response('Unauthorized', 403);
        }

        $message = $request->input('message');
        if (is_array($message)) {
            $this->telegramUpdates->processMessage($message);
        }

        return response('ok', 200);
    }
}
