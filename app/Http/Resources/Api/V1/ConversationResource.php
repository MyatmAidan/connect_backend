<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $setting = $this->userSettings->first();

        return [
            'id' => $this->id,
            'connection_id' => $this->connection_id,
            'last_message_at' => $this->last_message_at,
            'is_pinned' => (bool) ($setting?->is_pinned ?? false),
            'pin_order' => $setting?->pin_order,
            'is_muted' => (bool) ($setting?->is_muted ?? false),
            'connection' => ConnectionResource::make($this->whenLoaded('connection')),
            'last_message' => MessageResource::make($this->whenLoaded('latestMessage')),
        ];
    }
}
