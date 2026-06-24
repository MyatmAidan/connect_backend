<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>|null  $keyboard
     * @return array{ok: bool, error: string|null, result: mixed}
     */
    public function sendMessage(string $chatId, string $text, ?array $keyboard = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram bot token is not configured.', 'result' => null];
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ];

        if ($keyboard !== null) {
            $payload['reply_markup'] = [
                'inline_keyboard' => $keyboard,
            ];
        }

        return $this->callApi('sendMessage', $payload);
    }

    /**
     * @return array{ok: bool, error: string|null, result: mixed}
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram bot token is not configured.', 'result' => null];
        }

        $payload = ['callback_query_id' => $callbackQueryId];

        if ($text !== null) {
            $payload['text'] = mb_substr($text, 0, 200);
            $payload['show_alert'] = false;
        }

        return $this->callApi('answerCallbackQuery', $payload);
    }

    /**
     * @return array{ok: bool, error: string|null, result: mixed}
     */
    public function clearInlineKeyboard(string $chatId, int $messageId): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram bot token is not configured.', 'result' => null];
        }

        return $this->callApi('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => ['inline_keyboard' => []],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, error: string|null, result: mixed}
     */
    private function callApi(string $method, array $payload): array
    {
        $token = config('services.telegram.bot_token');

        try {
            $response = Http::timeout(15)
                ->asJson()
                ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'error' => $exception->getMessage(),
                'result' => null,
            ];
        }

        if ($response->successful() && $response->json('ok') === true) {
            return [
                'ok' => true,
                'error' => null,
                'result' => $response->json('result'),
            ];
        }

        return [
            'ok' => false,
            'error' => $response->json('description') ?? $response->body(),
            'result' => null,
        ];
    }
}
