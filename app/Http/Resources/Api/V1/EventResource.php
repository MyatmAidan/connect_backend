<?php

namespace App\Http\Resources\Api\V1;

use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $myRegistration = $this->myRegistrationFor($request, $user);

        return [
            'id' => $this->id,
            'created_by' => $this->created_by,
            'title' => $this->title,
            'section' => $this->section,
            'event_date' => $this->event_date,
            'photo' => $this->photo,
            'meeting_url' => $this->meeting_url,
            'display_order' => $this->display_order,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'can_manage_registrations' => $user
                ? ($user->isAdmin() || $this->created_by === $user->id)
                : false,
            'registration_open' => $this->isRegistrationOpen(),
            'my_registration' => $myRegistration
                ? EventRegistrationResource::make($myRegistration)
                : null,
            'registrations_count' => $this->whenCounted('registrations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function myRegistrationFor(Request $request, ?\App\Models\User $user): ?EventRegistration
    {
        if (! $user) {
            return null;
        }

        if (! str_ends_with(trim($request->path(), '/'), (string) $this->id)) {
            return null;
        }

        return EventRegistration::query()
            ->where('event_id', $this->id)
            ->where('user_id', $user->id)
            ->first();
    }
}
