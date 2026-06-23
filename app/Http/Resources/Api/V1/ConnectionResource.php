<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_one_id' => $this->user_one_id,
            'user_two_id' => $this->user_two_id,
            'user_one' => UserResource::make($this->whenLoaded('userOne')),
            'user_two' => UserResource::make($this->whenLoaded('userTwo')),
            'conversation' => ConversationResource::make($this->whenLoaded('conversation')),
            'created_at' => $this->created_at,
        ];
    }
}
