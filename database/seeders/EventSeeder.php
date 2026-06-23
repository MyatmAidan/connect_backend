<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use App\Enums\EventRequestStatus;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', 'admin')->first()
            ?? User::query()->where('role', 'super_admin')->first();

        if (! $admin) {
            return;
        }

        $events = [
            [
                'title' => 'CONNECT Community Meetup',
                'section' => 'Networking',
                'meeting_url' => 'https://meet.google.com/connect-meetup',
            ],
            [
                'title' => 'Laravel Best Practices Workshop',
                'section' => 'Workshop',
                'meeting_url' => 'https://meet.google.com/laravel-workshop',
            ],
            [
                'title' => 'React Native Mobile Dev Talk',
                'section' => 'Tech Talk',
                'meeting_url' => 'https://meet.google.com/react-native-talk',
            ],
        ];

        foreach ($events as $data) {
            Event::query()->create([
                'created_by' => $admin->id,
                'event_date' => now()->addDays(random_int(7, 45))->toDateString(),
                ...$data,
            ]);
        }

        $developer = User::query()->where('role', 'user')->first();
        if ($developer) {
            EventRequest::query()->create([
                'user_id' => $developer->id,
                'title' => 'Open Source Contribution Day',
                'section' => 'Community',
                'event_date' => now()->addDays(14)->toDateString(),
                'message' => 'Would love to host a session for junior developers.',
                'meeting_url' => 'https://meet.google.com/opensource-day',
                'status' => EventRequestStatus::Pending->value,
            ]);
        }
    }
}
