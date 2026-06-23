<?php

namespace Database\Seeders;

use App\Models\AdminLog;
use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@connect.test')->first();

        if (! $admin) {
            return;
        }

        $actions = [
            ['action' => 'create_category', 'target_type' => Category::class, 'description' => 'Created category Backend'],
            ['action' => 'create_skill', 'target_type' => Skill::class, 'description' => 'Created skill Laravel'],
            ['action' => 'update_user', 'target_type' => User::class, 'description' => 'Updated user status'],
            ['action' => 'create_category', 'target_type' => Category::class, 'description' => 'Created category Frontend'],
            ['action' => 'create_skill', 'target_type' => Skill::class, 'description' => 'Created skill React'],
            ['action' => 'delete_skill', 'target_type' => Skill::class, 'description' => 'Deleted unused skill'],
            ['action' => 'update_category', 'target_type' => Category::class, 'description' => 'Updated category Mobile'],
            ['action' => 'broadcast_notification', 'target_type' => null, 'description' => 'Sent broadcast to all users'],
            ['action' => 'review_report', 'target_type' => null, 'description' => 'Reviewed user report'],
            ['action' => 'update_user', 'target_type' => User::class, 'description' => 'Updated user role'],
        ];

        $category = Category::query()->first();
        $skill = Skill::query()->first();
        $user = User::query()->where('email', 'dev@connect.test')->first();

        $targets = [
            $category?->id,
            $skill?->id,
            $user?->id,
            $category?->id,
            $skill?->id,
            $skill?->id,
            $category?->id,
            null,
            null,
            $user?->id,
        ];

        foreach ($actions as $index => $entry) {
            AdminLog::query()->updateOrCreate(
                [
                    'admin_id' => $admin->id,
                    'action' => $entry['action'],
                    'description' => $entry['description'],
                ],
                [
                    'target_type' => $entry['target_type'],
                    'target_id' => $targets[$index],
                ],
            );
        }
    }
}
