<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('email')->take(10)->get();
        $admin = $users->first();

        $reasons = [
            'spam',
            'harassment',
            'inappropriate_content',
            'fake_profile',
            'spam',
            'harassment',
            'other',
            'spam',
            'inappropriate_content',
            'other',
        ];

        $statuses = [
            ReportStatus::Pending,
            ReportStatus::Pending,
            ReportStatus::Pending,
            ReportStatus::Reviewed,
            ReportStatus::Resolved,
            ReportStatus::Rejected,
            ReportStatus::Pending,
            ReportStatus::Reviewed,
            ReportStatus::Resolved,
            ReportStatus::Pending,
        ];

        foreach ($users as $index => $reporter) {
            $reported = $users[($index + 5) % 10];
            $status = $statuses[$index];

            Report::query()->updateOrCreate(
                [
                    'reporter_id' => $reporter->id,
                    'reported_user_id' => $reported->id,
                    'reason' => $reasons[$index],
                ],
                [
                    'description' => 'Seeded report description '.($index + 1).'.',
                    'status' => $status->value,
                    'reviewed_by' => in_array($status, [ReportStatus::Reviewed, ReportStatus::Resolved, ReportStatus::Rejected], true)
                        ? $admin?->id
                        : null,
                    'reviewed_at' => in_array($status, [ReportStatus::Reviewed, ReportStatus::Resolved, ReportStatus::Rejected], true)
                        ? now()->subDays($index)
                        : null,
                ],
            );
        }
    }
}
