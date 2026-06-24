<?php

namespace App\Services;

use App\Models\User;
use App\Support\TelegramUrl;
use Illuminate\Support\Facades\Log;

class TelegramUpdateService
{
    public function __construct(
        private readonly TelegramNotificationService $telegramNotifications,
        private readonly TelegramService $telegram,
    ) {
    }

    /**
     * @param  array<string, mixed>  $message
     */
    public function processMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = is_string($message['text'] ?? null) ? trim($message['text']) : '';
        $username = $message['from']['username'] ?? null;

        if (! $chatId) {
            return;
        }

        $chatIdString = (string) $chatId;

        $linkToken = $this->extractStartPayload($text);

        if ($linkToken !== null) {
            $this->handleStartCommand($chatIdString, $username, $linkToken);

            return;
        }

        if ($this->handleCommand($chatIdString, $text)) {
            return;
        }

        $this->handleDefaultMessage($chatIdString, $text);
    }

    private function handleStartCommand(string $chatId, ?string $username, string $linkToken): void
    {
        if ($linkToken === '') {
            $this->reply($chatId, $this->linkInstructionsMessage());

            return;
        }

        $link = \App\Models\TelegramLinkToken::query()
            ->where('token', $linkToken)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $link) {
            Log::info('Telegram link token not found or expired', ['token_prefix' => substr($linkToken, 0, 8)]);
            $this->reply(
                $chatId,
                'This link expired or was already used. Go back to CONNECT → Settings → Telegram → Connect Telegram again.',
            );

            return;
        }

        User::query()->where('id', $link->user_id)->update([
            'telegram_chat_id' => $chatId,
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
        } else {
            $this->reply($chatId, "✅ CONNECT account linked.\n\nYou will receive notifications here.");
        }

        Log::info('Telegram account linked', ['user_id' => $link->user_id, 'chat_id' => $chatId]);
    }

    private function handleCommand(string $chatId, string $text): bool
    {
        $command = strtolower($text);

        if (! str_starts_with($command, '/')) {
            return false;
        }

        $command = strtok($command, '@') ?: $command;

        return match ($command) {
            '/help' => $this->reply($chatId, $this->helpMessage()) !== null,
            '/status' => $this->reply($chatId, $this->statusMessage($chatId)) !== null,
            '/start' => $this->reply($chatId, $this->linkInstructionsMessage()) !== null,
            default => $this->reply(
                $chatId,
                "Unknown command.\n\nSend /help to see available commands.",
            ) !== null,
        };
    }

    private function handleDefaultMessage(string $chatId, string $text): void
    {
        if ($text === '') {
            $this->reply($chatId, "I can only respond to text commands.\n\nSend /help for more info.");

            return;
        }

        $user = $this->findLinkedUser($chatId);

        if ($user) {
            $this->reply(
                $chatId,
                "Hi {$user->name}! You are connected to CONNECT.\n\n".
                "You will receive friend requests and other alerts here.\n".
                "Send /help for commands.",
            );

            return;
        }

        $this->reply($chatId, $this->linkInstructionsMessage());
    }

    private function helpMessage(): string
    {
        return implode("\n", [
            '🤖 CONNECT Telegram Bot',
            '',
            '/start — Link your CONNECT account',
            '/status — Check link status',
            '/help — Show this message',
            '',
            'To link: open CONNECT app → Settings → Telegram → Connect Telegram, then tap Start here.',
            '',
            'When someone sends you a friend request, you will get a message with Accept / Reject buttons.',
        ]);
    }

    private function linkInstructionsMessage(): string
    {
        return implode("\n", [
            'Welcome to CONNECT notifications.',
            '',
            '1. Open the CONNECT mobile app',
            '2. Go to Settings → Telegram',
            '3. Tap Connect Telegram',
            '4. Press Start in this chat',
            '',
            'Send /help for more commands.',
        ]);
    }

    private function statusMessage(string $chatId): string
    {
        $user = $this->findLinkedUser($chatId);

        if (! $user) {
            return "❌ Not linked.\n\n".$this->linkInstructionsMessage();
        }

        $notify = $user->telegram_notify_enabled ? 'enabled' : 'disabled';

        return implode("\n", [
            '✅ CONNECT account linked',
            '',
            'Name: '.$user->name,
            'Notifications: '.$notify,
            'Linked at: '.($user->telegram_linked_at?->toDateTimeString() ?? 'unknown'),
        ]);
    }

    private function findLinkedUser(string $chatId): ?User
    {
        return User::query()->where('telegram_chat_id', $chatId)->first();
    }

  /**
     * @return array{ok: bool, error: string|null, result: mixed}|null
     */
    private function reply(string $chatId, string $text): ?array
    {
        if (! $this->telegram->isConfigured()) {
            Log::warning('Telegram bot token missing — cannot reply to chat', ['chat_id' => $chatId]);

            return null;
        }

        $result = $this->telegram->sendMessage($chatId, $text);

        if (! $result['ok']) {
            Log::warning('Telegram reply failed', [
                'chat_id' => $chatId,
                'error' => $result['error'],
            ]);
        }

        return $result;
    }

    /** Extract deep-link payload from /start or /start@BotName messages. */
    public function extractStartPayload(string $text): ?string
    {
        if (! preg_match('/^\/start(?:@\S+)?(?:\s+(.+))?$/i', trim($text), $matches)) {
            return null;
        }

        return isset($matches[1]) ? trim($matches[1]) : '';
    }
}
