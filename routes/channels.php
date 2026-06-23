<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Private channel for messages and typing whispers (unchanged).
Broadcast::channel('conversation.{conversationId}', function ($user, string $conversationId) {
    $conversation = Conversation::query()->with('connection')->find($conversationId);

    if (! $conversation) {
        return false;
    }

    $connection = $conversation->connection;

    return in_array($user->id, [$connection->user_one_id, $connection->user_two_id], true);
});

// Global presence channel — every authenticated user joins this on app start.
// Returning an array makes it a presence channel so members see each other.
Broadcast::channel('online', function ($user) {
    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
});
