<?php

namespace Database\Seeders;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('email')->take(10)->get();

        $types = [
            'friend_request_received',
            'friend_request_accepted',
            'new_message',
            'event_published',
            'event_reminder',
            'profile_viewed',
            'system_announcement',
            'friend_request_received',
            'new_message',
            'event_published',
        ];

        foreach ($users as $index => $user) {
            NotificationLog::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $types[$index],
                    'title' => 'Seeded notification '.($index + 1),
                ],
                [
                    'channel' => $index % 2 === 0 ? 'in_app' : 'telegram',
                    'body' => 'This is sample notification content for testing.',
                    'payload' => ['seed' => true, 'index' => $index + 1],
                    'status' => $index < 7 ? 'sent' : 'pending',
                    'read_at' => $index % 3 === 0 ? now() : null,
                    'sent_at' => $index < 7 ? now()->subHours($index) : null,
                    'error_message' => null,
                ],
            );
        }
    }
}
