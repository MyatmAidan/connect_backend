<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'email' => 'company@connect.test',
                'name' => 'TechWave HR',
                'company_name' => 'TechWave Solutions',
                'description' => 'Software consultancy hiring full-stack and mobile developers.',
                'location' => 'Yangon, Myanmar',
                'industry' => 'Technology',
                'is_verified' => true,
            ],
            [
                'email' => 'hr@innovate.test',
                'name' => 'Innovate HR',
                'company_name' => 'Innovate Labs',
                'description' => 'Product studio building SaaS platforms.',
                'location' => 'Singapore',
                'industry' => 'Software',
                'is_verified' => true,
            ],
        ];

        foreach ($companies as $data) {
            $company = Company::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            CompanyProfile::query()->updateOrCreate(
                ['company_id' => $company->id],
                [
                    'company_name' => $data['company_name'],
                    'description' => $data['description'],
                    'location' => $data['location'],
                    'industry' => $data['industry'],
                    'contact_email' => $data['email'],
                    'contact_phone' => '+959000000001',
                    'is_verified' => $data['is_verified'],
                    'is_active' => true,
                ],
            );
        }
    }
}
