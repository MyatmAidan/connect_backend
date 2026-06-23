<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name_en' => 'Backend', 'name_my' => 'နောက်ခံ'],
            ['name_en' => 'Frontend', 'name_my' => 'ရှေ့ခန်း'],
            ['name_en' => 'Full Stack', 'name_my' => 'ဖုလ်စတက်'],
            ['name_en' => 'Mobile', 'name_my' => 'မိုဘိုင်း'],
            ['name_en' => 'DevOps', 'name_my' => 'DevOps'],
            ['name_en' => 'Database', 'name_my' => 'ဒေတာဘေ့စ်'],
            ['name_en' => 'Tools', 'name_my' => 'ကိရိယာများ'],
            ['name_en' => 'Cloud', 'name_my' => 'ကလောက်'],
            ['name_en' => 'Security', 'name_my' => 'လုံခြုံရေး'],
            ['name_en' => 'AI/ML', 'name_my' => 'AI/ML'],
            ['name_en' => 'Design', 'name_my' => 'ဒီဇိုင်း'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name_en'])],
                $category,
            );
        }
    }
}
