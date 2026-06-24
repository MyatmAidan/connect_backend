<?php

namespace App\Services;

use App\Enums\ConnectionRequestStatus;
use App\Models\ConnectionRequest;
use App\Models\User;
use App\Support\TelegramUrl;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TelegramCallbackService
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly ConnectionRequestService $connectionRequests,
    ) {
    }

    /**
     * @param  array<string, mixed>  $callbackQuery
     */
    public function processCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'] ?? null;
        $data = is_string($callbackQuery['data'] ?? null) ? $callbackQuery['data'] : '';
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;

        if (! is_string($callbackId) || $data === '' || $chatId === null) {
            return;
        }

        $chatIdString = (string) $chatId;

        $user = User::query()
            ->where('telegram_chat_id', $chatIdString)
            ->first();

        if (! $user) {
            $this->answer($callbackId, 'Telegram account is not linked to CONNECT.');

            return;
        }

        if ($data === 'requests_received') {
            $this->handleOpenRequests($callbackId, $chatIdString);

            return;
        }

        if (preg_match('/^profile_(.+)$/', $data, $matches)) {
            $this->handleViewProfile($callbackId, $chatIdString, $matches[1]);

            return;
        }

        if (! preg_match('/^(accept|reject|message)_(.+)$/', $data, $matches)) {
            $this->answer($callbackId, 'Unknown action.');

            return;
        }

        $action = $matches[1];
        $requestId = $matches[2];

        $request = ConnectionRequest::query()
            ->with(['sender', 'receiver'])
            ->find($requestId);

        if (! $request) {
            $this->answer($callbackId, 'Friend request not found.');

            return;
        }

        if ($request->receiver_id !== $user->id) {
            $this->answer($callbackId, 'You are not allowed to act on this request.');

            return;
        }

        if ($action === 'message') {
            $this->handleMessageAction($callbackId, $request);

            return;
        }

        if ($request->status !== ConnectionRequestStatus::Pending) {
            $this->answer(
                $callbackId,
                'This request is already '.$request->status->value.'.',
            );
            $this->clearKeyboard($chatIdString, $messageId);

            return;
        }

        $this->answer($callbackId, $action === 'accept' ? 'Accepting…' : 'Rejecting…');

        try {
            if ($action === 'accept') {
                $this->connectionRequests->accept($request, $user);
                $this->clearKeyboard($chatIdString, $messageId);
                $this->telegram->sendMessage(
                    $chatIdString,
                    '✅ You accepted the connection request from '.($request->sender?->name ?? 'this developer').'.',
                );

                return;
            }

            if ($action === 'reject') {
                $this->connectionRequests->reject($request, $user);
                $this->clearKeyboard($chatIdString, $messageId);
                $this->telegram->sendMessage(
                    $chatIdString,
                    '❌ You rejected the connection request from '.($request->sender?->name ?? 'this developer').'.',
                );
            }
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? 'Action failed. Please try again in the app.';

            Log::warning('Telegram callback validation failed', [
                'action' => $action,
                'request_id' => $requestId,
                'user_id' => $user->id,
                'errors' => $exception->errors(),
            ]);

            $this->telegram->sendMessage($chatIdString, '⚠️ '.$message);
        } catch (\Throwable $exception) {
            Log::warning('Telegram callback action failed', [
                'action' => $action,
                'request_id' => $requestId,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            $this->telegram->sendMessage($chatIdString, '⚠️ Action failed. Please try again in the CONNECT app.');
        }
    }

    private function handleOpenRequests(string $callbackId, string $chatId): void
    {
        $url = TelegramUrl::mobileAppPath('requests/received');

        if ($url) {
            $this->answer($callbackId, 'Opening requests in CONNECT…');
            $this->telegram->sendMessage($chatId, "📥 View received requests:\n{$url}");

            return;
        }

        $this->answer($callbackId, 'Open the CONNECT app → Connection Requests.');
    }

    private function handleViewProfile(string $callbackId, string $chatId, string $userId): void
    {
        $url = TelegramUrl::mobileAppPath('developers/'.$userId);

        if ($url) {
            $this->answer($callbackId, 'Opening profile…');
            $this->telegram->sendMessage($chatId, "👤 View profile:\n{$url}");

            return;
        }

        $sender = User::query()->find($userId);
        $name = $sender?->name ?? 'this developer';

        $this->answer($callbackId, 'Open the CONNECT app to view the profile.');
        $this->telegram->sendMessage($chatId, "👤 View {$name}'s profile in the CONNECT app.");
    }

    private function handleMessageAction(string $callbackId, ConnectionRequest $request): void
    {
        $chatId = (string) ($request->receiver?->telegram_chat_id ?? '');

        if ($request->status === ConnectionRequestStatus::Accepted) {
            $chatUrl = TelegramUrl::mobileAppPath('tabs/chat');

            $this->answer($callbackId, 'Open CONNECT to start chatting.');

            if ($chatUrl && $chatId !== '') {
                $this->telegram->sendMessage($chatId, "💬 Chat is available in CONNECT:\n{$chatUrl}");
            } elseif ($chatId !== '') {
                $this->telegram->sendMessage($chatId, '💬 Open the CONNECT app → Chat to message them.');
            }

            return;
        }

        $this->answer($callbackId, 'Accept the request first to start messaging.');
    }

    private function clearKeyboard(string $chatId, mixed $messageId): void
    {
        if (! is_int($messageId)) {
            return;
        }

        $result = $this->telegram->clearInlineKeyboard($chatId, $messageId);

        if (! $result['ok']) {
            Log::debug('Telegram clearInlineKeyboard failed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'error' => $result['error'],
            ]);
        }
    }

    private function answer(string $callbackId, string $text): void
    {
        $result = $this->telegram->answerCallbackQuery($callbackId, $text);

        if (! $result['ok']) {
            Log::warning('Telegram answerCallbackQuery failed', [
                'callback_id' => $callbackId,
                'error' => $result['error'],
            ]);
        }
    }
}
