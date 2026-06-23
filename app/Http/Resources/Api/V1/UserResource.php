<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'status' => $this->status,
            'last_active_at' => $this->last_active_at,
            'telegram_username' => $this->telegram_username,
            'telegram_notify_enabled' => $this->telegram_notify_enabled,
            'telegram_linked_at' => $this->telegram_linked_at,
            'developer_profile' => DeveloperProfileResource::make($this->whenLoaded('developerProfile')),
            'created_at' => $this->created_at,
        ];
    }
}
