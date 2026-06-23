<?php

namespace Database\Seeders;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlockedUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('email')->take(10)->get();

        foreach ($users as $index => $blocker) {
            $blocked = $users[($index + 3) % 10];

            if ($blocker->id === $blocked->id) {
                $blocked = $users[($index + 4) % 10];
            }

            BlockedUser::query()->updateOrCreate(
                [
                    'blocker_id' => $blocker->id,
                    'blocked_id' => $blocked->id,
                ],
            );
        }
    }
}
