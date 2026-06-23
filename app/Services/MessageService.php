<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function __construct(
        private readonly MessageRepositoryInterface $messages,
    ) {
    }

    public function list(Conversation $conversation, User $user, int $perPage = 30)
    {
        $this->assertParticipant($conversation, $user);

        return $this->messages->paginate(['conversation_id' => $conversation->id], $perPage);
    }

    public function store(Conversation $conversation, User $user, array $data): Message
    {
        $this->assertParticipant($conversation, $user);

        return DB::transaction(function () use ($conversation, $user, $data) {
            $message = $this->messages->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'body' => $data['body'],
                'type' => $data['type'] ?? 'text',
            ]);

            $conversation->update(['last_message_at' => now()]);

            $message->load('sender');
            broadcast(new MessageSent($message))->toOthers();

            return $message;
        });
    }

    public function pin(Conversation $conversation, Message $message, User $user): Message
    {
        $this->assertParticipant($conversation, $user);
        $this->assertMessageInConversation($conversation, $message);

        if ($message->isDeleted()) {
            throw ValidationException::withMessages([
                'message' => ['Deleted messages cannot be pinned.'],
            ]);
        }

        $message->update([
            'pinned_at' => now(),
            'pinned_by' => $user->id,
        ]);

        return $message->fresh(['sender', 'pinnedBy']);
    }

    public function unpin(Conversation $conversation, Message $message, User $user): Message
    {
        $this->assertParticipant($conversation, $user);
        $this->assertMessageInConversation($conversation, $message);

        $message->update([
            'pinned_at' => null,
            'pinned_by' => null,
        ]);

        return $message->fresh(['sender', 'pinnedBy']);
    }

    public function update(Conversation $conversation, Message $message, User $user, array $data): Message
    {
        $this->assertParticipant($conversation, $user);
        $this->assertMessageInConversation($conversation, $message);

        if ($message->sender_id !== $user->id) {
            throw ValidationException::withMessages([
                'message' => ['You can only edit your own messages.'],
            ]);
        }

        if ($message->isDeleted()) {
            throw ValidationException::withMessages([
                'message' => ['Deleted messages cannot be edited.'],
            ]);
        }

        $message->update([
            'body' => $data['body'],
            'edited_at' => now(),
        ]);

        return $message->fresh('sender');
    }

    public function delete(Conversation $conversation, Message $message, User $user): Message
    {
        $this->assertParticipant($conversation, $user);
        $this->assertMessageInConversation($conversation, $message);

        if ($message->sender_id !== $user->id) {
            throw ValidationException::withMessages([
                'message' => ['You can only delete your own messages.'],
            ]);
        }

        if ($message->isDeleted()) {
            return $message;
        }

        $message->update([
            'deleted_at' => now(),
            'pinned_at' => null,
            'pinned_by' => null,
        ]);

        return $message->fresh('sender');
    }

    public function markAsRead(Conversation $conversation, User $user): int
    {
        $this->assertParticipant($conversation, $user);

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function assertParticipant(Conversation $conversation, User $user): void
    {
        $connection = $conversation->connection;
        if (! in_array($user->id, [$connection->user_one_id, $connection->user_two_id], true)) {
            throw ValidationException::withMessages([
                'conversation' => ['You do not have access to this conversation.'],
            ]);
        }
    }

    private function assertMessageInConversation(Conversation $conversation, Message $message): void
    {
        if ($message->conversation_id !== $conversation->id) {
            throw ValidationException::withMessages([
                'message' => ['Message does not belong to this conversation.'],
            ]);
        }
    }
}
