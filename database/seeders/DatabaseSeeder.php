<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            SkillSeeder::class,
            CompanySeeder::class,
            DeveloperProfileSeeder::class,
            JobSeeder::class,
            JobApplicationSeeder::class,
            SocialSeeder::class,
            EventSeeder::class,
            NotificationLogSeeder::class,
            ReportSeeder::class,
            BlockedUserSeeder::class,
            AdminLogSeeder::class,
            TelegramLinkTokenSeeder::class,
        ]);
    }
}
