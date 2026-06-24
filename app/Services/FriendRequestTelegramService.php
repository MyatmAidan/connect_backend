<?php

namespace App\Services;

use App\Models\ConnectionRequest;
use App\Models\NotificationLog;
use App\Models\User;
use App\Support\TelegramUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FriendRequestTelegramService
{
    public function __construct(
        private readonly TrustScoreService $trustScore,
        private readonly TelegramService $telegram,
    ) {
    }

    public function notifyReceiver(ConnectionRequest $request): void
    {
        $request->loadMissing(['sender.developerProfile.skills', 'receiver']);

        $receiver = $request->receiver;
        $sender = $request->sender;

        if (! $receiver || ! $sender) {
            return;
        }

        if (! $receiver->telegram_chat_id || ! $receiver->telegram_notify_enabled) {
            return;
        }

        if (! $this->telegram->isConfigured()) {
            return;
        }

        $alreadySent = NotificationLog::query()
            ->where('user_id', $receiver->id)
            ->where('channel', 'telegram')
            ->where('type', 'connection_request_received')
            ->where('status', 'sent')
            ->where('payload->connection_request_id', $request->id)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $trust = $this->trustScore->forUser($sender);
        $message = $this->buildMessage($request, $sender, $trust);
        $keyboard = $this->buildKeyboard($request, $sender);

        $log = DB::transaction(function () use ($receiver, $request, $sender, $trust, $message) {
            $existing = NotificationLog::query()
                ->where('user_id', $receiver->id)
                ->where('channel', 'telegram')
                ->where('type', 'connection_request_received')
                ->where('payload->connection_request_id', $request->id)
                ->whereIn('status', ['pending', 'sent'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return null;
            }

            return NotificationLog::query()->create([
                'user_id' => $receiver->id,
                'channel' => 'telegram',
                'type' => 'connection_request_received',
                'title' => 'New Connection Request',
                'body' => $message,
                'payload' => [
                    'connection_request_id' => $request->id,
                    'sender_id' => $sender->id,
                    'trust_score' => $trust['score'],
                    'trust_level' => $trust['level'],
                ],
                'status' => 'pending',
            ]);
        });

        if (! $log) {
            return;
        }

        $result = $this->telegram->sendMessage((string) $receiver->telegram_chat_id, $message, $keyboard);

        if (! $result['ok'] && $this->shouldRetryWithoutUrlButtons($result['error'])) {
            Log::warning('Telegram friend request send failed, retrying without URL buttons', [
                'request_id' => $request->id,
                'error' => $result['error'],
            ]);

            $keyboard = $this->buildKeyboard($request, $sender, useUrlButtons: false);
            $result = $this->telegram->sendMessage((string) $receiver->telegram_chat_id, $message, $keyboard);
        }

        if (! $result['ok'] && $this->shouldRetryWithoutKeyboard($result['error'])) {
            Log::warning('Telegram friend request send failed, retrying without keyboard', [
                'request_id' => $request->id,
                'error' => $result['error'],
            ]);

            $result = $this->telegram->sendMessage((string) $receiver->telegram_chat_id, $message);
        }

        if ($result['ok']) {
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return;
        }

        $log->update([
            'status' => 'failed',
            'error_message' => $result['error'],
        ]);
    }

    /**
     * @param  array{
     *     score: int,
     *     level: string,
     *     badges: list<string>,
     *     fields: array<string, string|null>
     * }  $trust
     */
    private function buildMessage(ConnectionRequest $request, User $sender, array $trust): string
    {
        $fields = $trust['fields'];

        $lines = [
            '👤 New Connection Request',
            '',
            'Name: '.($sender->name ?? 'Unknown'),
            'Role: '.($fields['role'] ?? 'Not provided'),
            'Skills: '.($fields['skills'] ?? 'Not provided'),
            'Experience: '.($fields['experience'] ?? 'Not provided'),
            '',
            '🔗 Portfolio: '.($fields['portfolio_url'] ?? 'N/A'),
            '',
            "⭐ Trust Score: {$trust['score']}/100 ({$trust['level']})",
        ];

        if ($trust['badges'] !== []) {
            $lines[] = '';
            $lines[] = 'Badges:';
            foreach ($trust['badges'] as $badge) {
                $lines[] = '• '.$badge;
            }
        }

        if (filled($request->message)) {
            $lines[] = '';
            $lines[] = 'Message:';
            $lines[] = '"'.$request->message.'"';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function buildKeyboard(ConnectionRequest $request, User $sender, bool $useUrlButtons = true): array
    {
        $profileUrl = TelegramUrl::mobileAppPath('developers/'.$sender->id);
        $requestsUrl = TelegramUrl::mobileAppPath('requests/received');

        $rows = [
            [
                ['text' => '✅ Accept', 'callback_data' => 'accept_'.$request->id],
                ['text' => '❌ Reject', 'callback_data' => 'reject_'.$request->id],
            ],
        ];

        if ($useUrlButtons && $profileUrl) {
            $rows[] = [
                ['text' => '👤 View Profile', 'url' => $profileUrl],
            ];
        } else {
            $rows[] = [
                ['text' => '👤 View Profile', 'callback_data' => 'profile_'.$sender->id],
            ];
        }

        $actionRow = [
            ['text' => '💬 Message', 'callback_data' => 'message_'.$request->id],
        ];

        if ($useUrlButtons && $requestsUrl) {
            $actionRow[] = ['text' => '📥 Open Requests', 'url' => $requestsUrl];
        } else {
            $actionRow[] = ['text' => '📥 Open Requests', 'callback_data' => 'requests_received'];
        }

        $rows[] = $actionRow;

        return $rows;
    }

    private function shouldRetryWithoutUrlButtons(?string $error): bool
    {
        if (! is_string($error) || $error === '') {
            return false;
        }

        return str_contains($error, 'BUTTON_URL_INVALID')
            || str_contains($error, 'inline keyboard')
            || str_contains($error, 'reply markup');
    }

    private function shouldRetryWithoutKeyboard(?string $error): bool
    {
        if (! is_string($error) || $error === '') {
            return false;
        }

        return str_contains($error, 'reply markup')
            || str_contains($error, 'BUTTON_');
    }
}
