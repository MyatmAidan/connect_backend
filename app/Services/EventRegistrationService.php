<?php

namespace App\Services;

use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class EventRegistrationService
{
    public function __construct(
        private readonly EventRegistrationRepositoryInterface $registrations,
        private readonly UserNotificationService $notifications,
    ) {
    }

    public function canManage(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->created_by === $user->id;
    }

    public function isRegistrationOpen(Event $event): bool
    {
        return $event->isRegistrationOpen();
    }

    public function register(User $user, Event $event, array $data): EventRegistration
    {
        if (! $this->isRegistrationOpen($event)) {
            throw ValidationException::withMessages([
                'event' => ['Registration for this event is closed.'],
            ]);
        }

        if ($event->created_by === $user->id) {
            throw ValidationException::withMessages([
                'event' => ['You cannot register for your own event.'],
            ]);
        }

        $existing = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'event' => ['You have already registered for this event.'],
            ]);
        }

        $registration = $this->registrations->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => EventRegistrationStatus::Pending->value,
        ]);

        $registration->load(['user', 'event.creator']);

        $this->notifications->eventRegistrationSubmitted($registration);

        return $registration;
    }

    public function accept(EventRegistration $registration, User $reviewer): EventRegistration
    {
        $this->assertCanReview($registration, $reviewer);

        if ($registration->status !== EventRegistrationStatus::Pending) {
            throw ValidationException::withMessages([
                'registration' => ['Only pending registrations can be accepted.'],
            ]);
        }

        $updated = $this->registrations->update($registration, [
            'status' => EventRegistrationStatus::Accepted->value,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $updated->load(['user', 'event', 'reviewer']);

        $this->notifications->eventRegistrationAccepted($updated);

        return $updated;
    }

    public function reject(EventRegistration $registration, User $reviewer): EventRegistration
    {
        $this->assertCanReview($registration, $reviewer);

        if ($registration->status !== EventRegistrationStatus::Pending) {
            throw ValidationException::withMessages([
                'registration' => ['Only pending registrations can be rejected.'],
            ]);
        }

        $updated = $this->registrations->update($registration, [
            'status' => EventRegistrationStatus::Rejected->value,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $updated->load(['user', 'event', 'reviewer']);

        $this->notifications->eventRegistrationRejected($updated);

        return $updated;
    }

    private function assertCanReview(EventRegistration $registration, User $reviewer): void
    {
        $registration->loadMissing('event');

        if (! $this->canManage($reviewer, $registration->event)) {
            throw new AuthorizationException('You are not allowed to review event registrations.');
        }
    }
}
