<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Models\CompanyProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class JobService
{
    public function listPublic(array $filters, int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        $query = Job::query()
            ->with(['companyProfile.company', 'category'])
            ->where('status', JobStatus::Open)
            ->whereHas('companyProfile', fn($q) => $q->where('is_active', true))
            ->latest('published_at');

        if ($user) {
            $query->withExists([
                'applications as has_applied' => fn($q) => $q->where('applicant_id', $user->id),
            ]);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        if (! empty($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->paginate($perPage);
    }

    public function showPublic(string $id, ?User $user = null): ?Job
    {
        $query = Job::query()
            ->with(['companyProfile.company', 'category'])
            ->where('status', JobStatus::Open)
            ->whereHas('companyProfile', fn($q) => $q->where('is_active', true));

        if ($user) {
            $query
                ->withExists([
                    'applications as has_applied' => fn($q) => $q->where('applicant_id', $user->id),
                ])
                ->with([
                    'applications' => fn($q) => $q->where('applicant_id', $user->id),
                ]);
        }

        return $query->find($id);
    }

    public function listForCompany(CompanyProfile $company, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $company->jobs()->with(['category'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function listAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Job::query()->with(['companyProfile.company', 'category'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['company_profile_id'])) {
            $query->where('company_profile_id', $filters['company_profile_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('title', 'like', "%{$search}%");
        }

        return $query->paginate($perPage);
    }

    public function create(CompanyProfile $company, array $data): Job
    {
        if (! $company->is_active) {
            throw ValidationException::withMessages([
                'company' => ['Your company account is not active.'],
            ]);
        }

        $job = $company->jobs()->create([
            ...collect($data)->only([
                'category_id',
                'title',
                'description',
                'requirements',
                'employment_type',
                'experience_level',
                'location',
                'salary_min',
                'salary_max',
                'salary_currency',
            ])->toArray(),
            'status' => $data['status'] ?? JobStatus::Draft,
        ]);

        return $job->load(['companyProfile.company', 'category']);
    }

    public function update(Job $job, array $data): Job
    {
        $job->update(collect($data)->only([
            'category_id',
            'title',
            'description',
            'requirements',
            'employment_type',
            'experience_level',
            'location',
            'salary_min',
            'salary_max',
            'salary_currency',
            'status',
            'closes_at',
        ])->filter(fn($value) => $value !== null)->toArray());

        return $job->fresh(['companyProfile.company', 'category']);
    }

    public function publish(Job $job): Job
    {
        $job->update([
            'status' => JobStatus::Open,
            'published_at' => now(),
        ]);

        return $job->fresh(['companyProfile.company', 'category']);
    }

    public function close(Job $job): Job
    {
        $job->update(['status' => JobStatus::Closed]);

        return $job->fresh(['companyProfile.company', 'category']);
    }

    public function delete(Job $job): void
    {
        $job->delete();
    }

    public function show(Job $job): Job
    {
        return $job->load(['companyProfile.company', 'category']);
    }
}
