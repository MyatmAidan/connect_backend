<?php

namespace Database\Seeders;

use App\Enums\JobApplicationStatus;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $developers = User::query()
            ->whereHas('developerProfile')
            ->orderBy('email')
            ->take(5)
            ->get();

        $jobs = Job::query()->where('status', 'open')->take(5)->get();

        foreach ($developers as $index => $developer) {
            $job = $jobs[$index] ?? null;

            if (! $job) {
                break;
            }

            JobApplication::query()->updateOrCreate(
                [
                    'job_posting_id' => $job->id,
                    'applicant_id' => $developer->id,
                ],
                [
                    'cover_letter' => 'I am interested in this role and believe my skills are a strong fit.',
                    'status' => JobApplicationStatus::Pending,
                ],
            );
        }
    }
}
