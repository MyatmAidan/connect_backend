<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@connect.test', 'name' => 'Super Admin', 'role' => UserRole::SuperAdmin],
            ['email' => 'dev@connect.test', 'name' => 'Demo Developer', 'role' => UserRole::User],
            ['email' => 'dev2@connect.test', 'name' => 'Second Developer', 'role' => UserRole::User],
            ['email' => 'dev3@connect.test', 'name' => 'Third Developer', 'role' => UserRole::User],
            ['email' => 'dev4@connect.test', 'name' => 'Fourth Developer', 'role' => UserRole::User],
            ['email' => 'dev5@connect.test', 'name' => 'Fifth Developer', 'role' => UserRole::User],
            ['email' => 'dev6@connect.test', 'name' => 'Sixth Developer', 'role' => UserRole::User],
            ['email' => 'dev7@connect.test', 'name' => 'Seventh Developer', 'role' => UserRole::User],
            ['email' => 'dev8@connect.test', 'name' => 'Eighth Developer', 'role' => UserRole::User],
            ['email' => 'dev9@connect.test', 'name' => 'Ninth Developer', 'role' => UserRole::User],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'role' => $user['role']->value,
                    'status' => UserStatus::Active->value,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
