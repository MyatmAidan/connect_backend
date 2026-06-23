<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanyProfileService
{
    public function createForCompany(Company $company, array $data): CompanyProfile
    {
        return CompanyProfile::query()->create([
            'company_id' => $company->id,
            'company_name' => $data['company_name'],
            'contact_email' => $data['contact_email'] ?? $company->email,
            'description' => $data['description'] ?? null,
            'website' => $data['website'] ?? null,
            'location' => $data['location'] ?? null,
            'industry' => $data['industry'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
        ]);
    }

    public function showForCompany(Company $company): ?CompanyProfile
    {
        return $company->companyProfile?->load('company');
    }

    public function update(CompanyProfile $profile, array $data): CompanyProfile
    {
        $profile->update(collect($data)->only([
            'company_name',
            'description',
            'logo',
            'website',
            'location',
            'industry',
            'contact_email',
            'contact_phone',
        ])->filter(fn($value) => $value !== null)->toArray());

        return $profile->fresh('company');
    }

    public function listAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = CompanyProfile::query()->with('company')->latest();

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('company', fn($cq) => $cq->where('email', 'like', "%{$search}%"));
            });
        }

        if (isset($filters['is_verified'])) {
            $query->where('is_verified', filter_var($filters['is_verified'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage);
    }

    public function adminUpdate(CompanyProfile $profile, array $data): CompanyProfile
    {
        $profile->update(collect($data)->only([
            'company_name',
            'description',
            'logo',
            'website',
            'location',
            'industry',
            'contact_email',
            'contact_phone',
            'is_verified',
            'is_active',
        ])->filter(fn($value) => $value !== null)->toArray());

        return $profile->fresh('company');
    }

    public function uploadLogo(Company $company, UploadedFile $file): string
    {
        $profile = $company->companyProfile;

        if (! $profile) {
            throw new \RuntimeException('Company profile not found.');
        }

        $this->deleteStoredFile($profile->logo);

        $path = $file->store('company-logos/' . $company->id, 'public');
        $url = '/storage/' . ltrim($path, '/');
        $profile->update(['logo' => $url]);

        return $url;
    }

    private function deleteStoredFile(?string $url): void
    {
        if (! $url || ! str_starts_with($url, '/storage/')) {
            return;
        }

        $relativePath = ltrim(substr($url, strlen('/storage/')), '/');

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
