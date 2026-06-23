<?php

namespace App\Services;

use App\Models\ConnectionRequest;
use App\Models\EventRegistration;
use App\Models\NotificationLog;
use App\Models\User;

class UserNotificationService
{
    public function __construct(private readonly TelegramNotificationService $telegramNotifications)
    {
    }

    public function connectionRequestReceived(ConnectionRequest $request): void
    {
        $request->loadMissing(['sender', 'receiver']);

        $senderName = $request->sender?->name ?? 'Someone';
        $body = "{$senderName} sent you a friend request.";

        if ($request->message) {
            $body .= "\n\n\"{$request->message}\"";
        }

        $this->notify(
            $request->receiver,
            'connection_request_received',
            'New friend request',
            $body,
            [
                'connection_request_id' => $request->id,
                'sender_id' => $request->sender_id,
            ],
        );
    }

    public function connectionRequestAccepted(ConnectionRequest $request): void
    {
        $request->loadMissing(['sender', 'receiver']);

        $receiverName = $request->receiver?->name ?? 'Someone';

        $this->notify(
            $request->sender,
            'connection_request_accepted',
            'Friend request accepted',
            "{$receiverName} accepted your friend request.",
            [
                'connection_request_id' => $request->id,
                'receiver_id' => $request->receiver_id,
            ],
        );
    }

    public function eventRegistrationSubmitted(EventRegistration $registration): void
    {
        $registration->loadMissing(['user', 'event.creator']);

        $eventTitle = $registration->event?->title ?? 'an event';
        $payload = $this->eventRegistrationPayload($registration, $eventTitle);

        $creatorBody = $this->eventRegistrationDetailsBody(
            $registration,
            $eventTitle,
            "New registration for \"{$eventTitle}\".",
        );

        $this->notify(
            $registration->event?->creator,
            'event_registration_received',
            'New event registration',
            $creatorBody,
            array_merge($payload, ['user_id' => $registration->user_id]),
        );

        $registrantBody = $this->eventRegistrationDetailsBody(
            $registration,
            $eventTitle,
            "Your registration for \"{$eventTitle}\" was submitted and is pending review.",
        );

        $this->notify(
            $registration->user,
            'event_registration_submitted',
            'Event registration submitted',
            $registrantBody,
            $payload,
        );
    }

    public function eventRegistrationAccepted(EventRegistration $registration): void
    {
        $registration->loadMissing(['user', 'event']);

        $eventTitle = $registration->event?->title ?? 'the event';

        $this->notify(
            $registration->user,
            'event_registration_accepted',
            'Event registration accepted',
            "Your registration for \"{$eventTitle}\" was accepted.",
            [
                'event_id' => $registration->event_id,
                'event_registration_id' => $registration->id,
            ],
        );
    }

    public function eventRegistrationRejected(EventRegistration $registration): void
    {
        $registration->loadMissing(['user', 'event']);

        $eventTitle = $registration->event?->title ?? 'the event';

        $this->notify(
            $registration->user,
            'event_registration_rejected',
            'Event registration declined',
            "Your registration for \"{$eventTitle}\" was declined.",
            [
                'event_id' => $registration->event_id,
                'event_registration_id' => $registration->id,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function eventRegistrationPayload(EventRegistration $registration, string $eventTitle): array
    {
        return [
            'event_id' => $registration->event_id,
            'event_registration_id' => $registration->id,
            'event_title' => $eventTitle,
            'registrant_name' => $registration->name,
            'registrant_email' => $registration->email,
            'registrant_phone' => $registration->phone,
            'registrant_message' => $registration->message,
        ];
    }

    private function eventRegistrationDetailsBody(
        EventRegistration $registration,
        string $eventTitle,
        string $intro,
    ): string {
        $lines = [
            $intro,
            '',
            'Name: '.$registration->name,
            'Email: '.$registration->email,
            'Phone: '.($registration->phone ?: '—'),
        ];

        if ($registration->message) {
            $lines[] = '';
            $lines[] = 'Message:';
            $lines[] = '"'.$registration->message.'"';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function notify(
        ?User $user,
        string $type,
        string $title,
        string $body,
        ?array $payload = null,
    ): void {
        if (! $user) {
            return;
        }

        NotificationLog::query()->create([
            'user_id' => $user->id,
            'channel' => 'in_app',
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        if (
            $user->telegram_chat_id
            && $user->telegram_notify_enabled
            && $this->telegramNotifications->isConfigured()
        ) {
            $this->telegramNotifications->sendToUser($user, $title, $body, $type);
        }
    }
}
