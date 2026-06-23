<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            ['name' => 'Laravel', 'category' => 'backend'],
            ['name' => 'React', 'category' => 'frontend'],
            ['name' => 'TypeScript', 'category' => 'frontend'],
            ['name' => 'Ionic', 'category' => 'mobile'],
            ['name' => 'Docker', 'category' => 'devops'],
            ['name' => 'PostgreSQL', 'category' => 'database'],
            ['name' => 'Git', 'category' => 'tools'],
            ['name' => 'AWS', 'category' => 'cloud'],
            ['name' => 'OAuth', 'category' => 'security'],
            ['name' => 'Python', 'category' => 'ai-ml'],
        ];

        foreach ($skills as $skill) {
            $category = Category::query()->where('slug', $skill['category'])->first();

            Skill::query()->updateOrCreate(
                ['slug' => Str::slug($skill['name'])],
                [
                    'name' => $skill['name'],
                    'category_id' => $category?->id,
                ],
            );
        }
    }
}
