<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $isDeleted = $this->deleted_at !== null;

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'body' => $isDeleted ? null : $this->body,
            'type' => $this->type,
            'read_at' => $this->read_at,
            'is_deleted' => $isDeleted,
            'is_pinned' => $this->pinned_at !== null,
            'pinned_at' => $this->pinned_at,
            'edited_at' => $this->edited_at,
            'can_edit' => $userId === $this->sender_id && ! $isDeleted,
            'can_delete' => $userId === $this->sender_id && ! $isDeleted,
            'sender' => UserResource::make($this->whenLoaded('sender')),
            'created_at' => $this->created_at,
        ];
    }
}
