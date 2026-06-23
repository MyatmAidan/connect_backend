<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TelegramNotificationService
{
    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    public function sendTest(User $user): NotificationLog
    {
        return $this->sendToUser(
            $user,
            'CONNECT test notification',
            'Your Telegram notifications are working!',
            'telegram_test',
        );
    }

    /**
     * @param  array<string>|null  $userIds
     * @return array{sent: int, failed: int, recipients: int}
     */
    public function broadcast(string $title, string $body, ?array $userIds = null): array
    {
        $users = $this->telegramRecipientsQuery($userIds)->get();

        return $this->dispatchToUsers($users, $title, $body);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return array{sent: int, failed: int, recipients: int}
     */
    private function dispatchToUsers($users, string $title, string $body): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $log = $this->sendToUser($user, $title, $body, 'system_broadcast');

            if ($log->status === 'sent') {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'recipients' => $users->count(),
        ];
    }

    /**
     * @param  array<string>|null  $userIds
     */
    private function telegramRecipientsQuery(?array $userIds)
    {
        $query = User::query()
            ->where('status', 'active')
            ->whereNotNull('telegram_chat_id');

        if ($userIds) {
            return $query->whereIn('id', $userIds);
        }

        return $query->where('telegram_notify_enabled', true);
    }

    public function sendToUser(
        User $user,
        string $title,
        string $body,
        string $type = 'notification',
    ): NotificationLog {
        $log = NotificationLog::query()->create([
            'user_id' => $user->id,
            'channel' => 'telegram',
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'status' => 'pending',
        ]);

        if (! $user->telegram_chat_id) {
            return $this->markFailed($log, 'Telegram is not connected.');
        }

        if (! $this->isConfigured()) {
            return $this->markFailed($log, 'Telegram bot token is not configured.');
        }

        $message = $this->formatMessage($title, $body);
        $result = $this->callSendMessage((string) $user->telegram_chat_id, $message);

        if ($result['ok']) {
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return $log->fresh();
        }

        return $this->markFailed($log, $result['error']);
    }

    private function callSendMessage(string $chatId, string $text): array
    {
        $token = config('services.telegram.bot_token');

        try {
            $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }

        if ($response->successful() && $response->json('ok') === true) {
            return ['ok' => true, 'error' => null];
        }

        return [
            'ok' => false,
            'error' => $response->json('description') ?? $response->body(),
        ];
    }

    /** Send a plain-text message without creating a notification log. */
    public function sendRawMessage(string $chatId, string $text): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $this->callSendMessage($chatId, $text);
    }

    private function formatMessage(string $title, string $body): string
    {
        $escapedTitle = $this->escapeHtml($title);
        $escapedBody = $this->escapeHtml($body);

        return "<b>{$escapedTitle}</b>\n\n{$escapedBody}";
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function markFailed(NotificationLog $log, string $error): NotificationLog
    {
        $log->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);

        return $log->fresh();
    }
}
