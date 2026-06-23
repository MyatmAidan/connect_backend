<?php

namespace App\Services;

use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramUpdateService
{
    public function __construct(private readonly TelegramNotificationService $telegramNotifications)
    {
    }

    /**
     * @param  array<string, mixed>  $message
     */
    public function processMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = is_string($message['text'] ?? null) ? $message['text'] : '';
        $username = $message['from']['username'] ?? null;

        if (! $chatId) {
            return;
        }

        $linkToken = $this->extractStartPayload($text);

        if ($linkToken === null) {
            return;
        }

        if ($linkToken === '') {
            $this->telegramNotifications->sendRawMessage(
                (string) $chatId,
                'Please open Telegram from the CONNECT app using Connect Telegram, then tap Start.',
            );

            return;
        }

        $link = TelegramLinkToken::query()
            ->where('token', $linkToken)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $link) {
            Log::info('Telegram link token not found or expired', ['token_prefix' => substr($linkToken, 0, 8)]);
            $this->telegramNotifications->sendRawMessage(
                (string) $chatId,
                'This link expired or was already used. Go back to CONNECT → Settings → Telegram → Connect Telegram again.',
            );

            return;
        }

        User::query()->where('id', $link->user_id)->update([
            'telegram_chat_id' => (string) $chatId,
            'telegram_username' => $username,
            'telegram_linked_at' => now(),
            'telegram_notify_enabled' => true,
        ]);

        $link->update(['used_at' => now()]);

        $linkedUser = User::query()->find($link->user_id);

        if ($linkedUser && $this->telegramNotifications->isConfigured()) {
            $this->telegramNotifications->sendToUser(
                $linkedUser,
                'CONNECT connected',
                'Your account is now linked. You will receive notifications here.',
                'telegram_linked',
            );
        }

        Log::info('Telegram account linked', ['user_id' => $link->user_id, 'chat_id' => $chatId]);
    }

    /** Extract deep-link payload from /start or /start@BotName messages. */
    public function extractStartPayload(string $text): ?string
    {
        if (! preg_match('/^\/start(?:@\S+)?(?:\s+(.+))?$/', trim($text), $matches)) {
            return null;
        }

        return isset($matches[1]) ? trim($matches[1]) : '';
    }
}
