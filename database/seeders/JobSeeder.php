<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Job;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $companies = CompanyProfile::query()->with('company')->get();
        $categories = Category::query()->pluck('id', 'slug');

        $jobs = [
            ['Senior Laravel Developer', 'backend', ExperienceLevel::Senior],
            ['React Native Engineer', 'mobile', ExperienceLevel::Mid],
            ['DevOps Specialist', 'devops', ExperienceLevel::Senior],
            ['Junior Full Stack Developer', 'full-stack', ExperienceLevel::Junior],
            ['UI Engineer', 'frontend', ExperienceLevel::Mid],
            ['Backend API Developer', 'backend', ExperienceLevel::Mid],
            ['Cloud Solutions Architect', 'cloud', ExperienceLevel::Lead],
            ['QA Automation Engineer', 'tools', ExperienceLevel::Mid],
            ['Data Engineer', 'database', ExperienceLevel::Senior],
            ['Technical Lead', 'full-stack', ExperienceLevel::Lead],
        ];

        $index = 0;
        foreach ($companies as $company) {
            foreach (array_slice($jobs, $index, 5) as [$title, $categorySlug, $level]) {
                Job::query()->updateOrCreate(
                    [
                        'company_profile_id' => $company->id,
                        'title' => $title,
                    ],
                    [
                        'category_id' => $categories[$categorySlug] ?? null,
                        'description' => "Join {$company->company_name} as a {$title}. Work with a collaborative team on real-world products.",
                        'requirements' => 'Strong communication, teamwork, and relevant technical experience.',
                        'employment_type' => EmploymentType::FullTime,
                        'experience_level' => $level,
                        'location' => $company->location,
                        'salary_min' => 800,
                        'salary_max' => 2500,
                        'salary_currency' => 'USD',
                        'status' => JobStatus::Open,
                        'published_at' => now()->subDays(rand(1, 14)),
                    ],
                );
            }
            $index += 5;
        }
    }
}
