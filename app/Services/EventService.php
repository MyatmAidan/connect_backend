<?php

namespace App\Services;

use App\Enums\EventRequestStatus;
use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\EventRequestRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly EventRequestRepositoryInterface $eventRequests,
    ) {
    }

    public function create(User $admin, array $data, ?UploadedFile $photo = null): Event
    {
        if ($photo) {
            $data['photo'] = $this->storePhoto($photo, 'events');
        }

        return $this->events->create([
            'created_by' => $admin->id,
            'display_order' => $this->nextDisplayOrder(),
            ...collect($data)->only(['title', 'section', 'event_date', 'photo', 'meeting_url'])->all(),
        ]);
    }

    public function update(Event $event, array $data, ?UploadedFile $photo = null): Event
    {
        if ($photo) {
            $this->deleteStoredPhoto($event->photo);
            $data['photo'] = $this->storePhoto($photo, 'events');
        }

        return $this->events->update($event, collect($data)->only(['title', 'section', 'event_date', 'photo', 'meeting_url'])->all());
    }

    public function delete(Event $event): void
    {
        $this->deleteStoredPhoto($event->photo);
        $this->events->delete($event);
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            Event::query()->where('id', $id)->update(['display_order' => $index + 1]);
        }
    }

    public function submitRequest(User $user, array $data, ?UploadedFile $photo = null): EventRequest
    {
        if ($photo) {
            $data['photo'] = $this->storePhoto($photo, 'event-requests/'.$user->id);
        }

        return $this->eventRequests->create([
            'user_id' => $user->id,
            'status' => EventRequestStatus::Pending->value,
            ...collect($data)->only(['title', 'section', 'event_date', 'photo', 'meeting_url', 'message'])->all(),
        ]);
    }

    public function approveRequest(EventRequest $request, User $admin): EventRequest
    {
        $event = $this->events->create([
            'created_by' => $request->user_id,
            'display_order' => $this->nextDisplayOrder(),
            'title' => $request->title,
            'section' => $request->section,
            'event_date' => $request->event_date,
            'photo' => $request->photo,
            'meeting_url' => $request->meeting_url,
        ]);

        return $this->eventRequests->update($request, [
            'status' => EventRequestStatus::Approved->value,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'event_id' => $event->id,
        ]);
    }

    public function rejectRequest(EventRequest $request, User $admin): EventRequest
    {
        return $this->eventRequests->update($request, [
            'status' => EventRequestStatus::Rejected->value,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    private function nextDisplayOrder(): int
    {
        return ((int) Event::query()->max('display_order')) + 1;
    }

    private function storePhoto(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');

        return $this->publicStorageUrl($path);
    }

    private function deleteStoredPhoto(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return;
        }

        $relative = ltrim(str_replace('/storage/', '', $path), '/');
        if ($relative !== '') {
            Storage::disk('public')->delete($relative);
        }
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.$path;
    }
}
