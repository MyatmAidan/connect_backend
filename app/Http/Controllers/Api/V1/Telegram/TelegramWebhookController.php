<?php

namespace App\Http\Controllers\Api\V1\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramCallbackService;
use App\Services\TelegramUpdateDeduplicator;
use App\Services\TelegramUpdateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramUpdateService $telegramUpdates,
        private readonly TelegramCallbackService $telegramCallbacks,
        private readonly TelegramUpdateDeduplicator $telegramDedup,
    ) {
    }

    public function handle(Request $request): Response
    {
        $secret = config('services.telegram.webhook_secret');
        $incomingSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($secret && $incomingSecret !== $secret) {
            Log::warning('Telegram webhook rejected: invalid secret token', [
                'has_incoming_secret' => filled($incomingSecret),
            ]);

            return response('Unauthorized', 403);
        }

        $updateId = $request->input('update_id');
        if ($this->telegramDedup->alreadyProcessed(is_numeric($updateId) ? (int) $updateId : null)) {
            return response('ok', 200);
        }

        $message = $request->input('message');
        if (is_array($message)) {
            Log::debug('Telegram webhook message', [
                'chat_id' => $message['chat']['id'] ?? null,
                'text' => $message['text'] ?? null,
            ]);
            $this->telegramUpdates->processMessage($message);
        }

        $callbackQuery = $request->input('callback_query');
        if (is_array($callbackQuery)) {
            Log::debug('Telegram webhook callback', [
                'data' => $callbackQuery['data'] ?? null,
            ]);
            $this->telegramCallbacks->processCallbackQuery($callbackQuery);
        }

        return response('ok', 200);
    }
}
