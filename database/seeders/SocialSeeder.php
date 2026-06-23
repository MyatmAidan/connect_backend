<?php

namespace Database\Seeders;

use App\Enums\ConnectionRequestStatus;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class SocialSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('email')->take(10)->get();

        if ($users->count() < 10) {
            return;
        }

        $requestStatuses = [
            ConnectionRequestStatus::Accepted,
            ConnectionRequestStatus::Accepted,
            ConnectionRequestStatus::Pending,
            ConnectionRequestStatus::Accepted,
            ConnectionRequestStatus::Rejected,
            ConnectionRequestStatus::Pending,
            ConnectionRequestStatus::Accepted,
            ConnectionRequestStatus::Accepted,
            ConnectionRequestStatus::Pending,
            ConnectionRequestStatus::Accepted,
        ];

        foreach ($users as $index => $sender) {
            $receiver = $users[($index + 1) % 10];

            ConnectionRequest::query()->updateOrCreate(
                [
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                ],
                [
                    'message' => 'Hi, let\'s connect and collaborate!',
                    'status' => $requestStatuses[$index]->value,
                ],
            );
        }

        $connections = [];

        foreach ($users as $index => $userA) {
            $userB = $users[($index + 2) % 10];
            [$userOne, $userTwo] = $this->orderedPair($userA->id, $userB->id);

            $connections[] = Connection::query()->updateOrCreate(
                [
                    'user_one_id' => $userOne,
                    'user_two_id' => $userTwo,
                ],
                ['connection_request_id' => null],
            );
        }

        foreach ($connections as $index => $connection) {
            $conversation = Conversation::query()->updateOrCreate(
                ['connection_id' => $connection->id],
                ['last_message_at' => now()->subMinutes(10 - $index)],
            );

            $senderId = $index % 2 === 0
                ? $connection->user_one_id
                : $connection->user_two_id;

            Message::query()->updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $senderId,
                    'body' => 'Sample message '.($index + 1).' in seeded conversation.',
                ],
                [
                    'type' => 'text',
                    'read_at' => $index % 2 === 0 ? now() : null,
                ],
            );
        }
    }

    private function orderedPair(string $a, string $b): array
    {
        return strcmp($a, $b) < 0 ? [$a, $b] : [$b, $a];
    }
}
