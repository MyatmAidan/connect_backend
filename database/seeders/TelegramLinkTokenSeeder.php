<?php

namespace Database\Seeders;

use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Database\Seeder;

class TelegramLinkTokenSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('email')->take(10)->get();

        foreach ($users as $index => $user) {
            TelegramLinkToken::query()->updateOrCreate(
                ['token' => 'seed-telegram-token-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $user->id,
                    'expires_at' => now()->addDays(7),
                    'used_at' => $index < 3 ? now()->subDays(1) : null,
                ],
            );
        }
    }
}
