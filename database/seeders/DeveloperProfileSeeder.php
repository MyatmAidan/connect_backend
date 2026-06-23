<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\DeveloperProfile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeveloperProfileSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('email')->get();
        $companyIds = Company::query()->pluck('id')->all();

        $roles = [
            'Full Stack Developer',
            'Frontend Engineer',
            'Backend Engineer',
            'Mobile Developer',
            'DevOps Engineer',
            'Database Administrator',
            'Cloud Architect',
            'Security Engineer',
            'ML Engineer',
            'UI/UX Developer',
        ];

        $levels = ['junior', 'mid', 'senior', 'lead', 'mid', 'senior', 'mid', 'senior', 'junior', 'mid'];
        $locations = [
            'Yangon, Myanmar',
            'Mandalay, Myanmar',
            'Naypyidaw, Myanmar',
            'Bangkok, Thailand',
            'Singapore',
            'Remote',
            'Yangon, Myanmar',
            'Mandalay, Myanmar',
            'Yangon, Myanmar',
            'Remote',
        ];

        $skillSlugs = Skill::query()->orderBy('name')->pluck('slug')->all();
        $categories = Category::query()->pluck('id', 'slug');

        $categorySlugs = [
            'full-stack', 'frontend', 'backend', 'mobile', 'devops',
            'database', 'cloud', 'security', 'ai-ml', 'design',
        ];

        foreach ($users->take(10) as $index => $user) {
            $profile = DeveloperProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'category_id' => $categories[$categorySlugs[$index]] ?? null,
                    'company_id' => $companyIds[$index % count($companyIds)] ?? null,
                    'headline' => $roles[$index].' open to collaboration',
                    'bio' => 'Passionate developer building great products with modern tools.',
                    'experience_level' => $levels[$index],
                    'location' => $locations[$index],
                    'github_url' => 'https://github.com/'.Str::slug($user->name),
                    'linkedin_url' => null,
                    'portfolio_url' => null,
                    'phone' => '+959'.str_pad((string) (700000000 + $index), 9, '0', STR_PAD_LEFT),
                    'is_public' => true,
                ],
            );

            if (isset($skillSlugs[$index])) {
                $skill = Skill::query()->where('slug', $skillSlugs[$index])->first();
                if ($skill) {
                    $profile->skills()->sync([
                        $skill->id => ['proficiency' => ($index % 3) + 3],
                    ]);
                }
            }
        }
    }
}
