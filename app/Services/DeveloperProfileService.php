<?php

namespace App\Services;

use App\Models\DeveloperProfile;
use App\Models\User;
use App\Repositories\Contracts\DeveloperProfileRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeveloperProfileService
{
    public function __construct(
        private readonly DeveloperProfileRepositoryInterface $profiles,
    ) {
    }

    public function list(array $filters, int $perPage = 15)
    {
        return $this->profiles->paginate($filters, $perPage);
    }

    public function show(string $id): ?DeveloperProfile
    {
        return $this->profiles->find($id)?->load(['user', 'category', 'employer.companyProfile', 'skills.category']);
    }

    public function showForUser(User $user): ?DeveloperProfile
    {
        return $user->developerProfile?->load(['category', 'employer.companyProfile', 'skills.category']);
    }

    public function store(User $user, array $data): DeveloperProfile
    {
        $user->loadMissing('developerProfile');

        if ($user->developerProfile) {
            return $this->update($user->developerProfile, $data);
        }

        $profile = $this->profiles->create([
            'user_id' => $user->id,
            ...collect($data)->only([
                'category_id', 'company_id', 'profile_photo', 'headline', 'bio', 'experience_level',
                'location', 'github_url', 'linkedin_url', 'portfolio_url', 'phone', 'is_public',
            ])->toArray(),
        ]);

        $this->syncSkills($profile, $data['skill_ids'] ?? []);
        $this->syncUserAvatar($user, $profile->profile_photo);

        return $profile->load(['user', 'category', 'skills.category']);
    }

    public function update(DeveloperProfile $profile, array $data): DeveloperProfile
    {
        $this->profiles->update($profile, collect($data)->only([
            'category_id', 'company_id', 'profile_photo', 'headline', 'bio', 'experience_level',
            'location', 'github_url', 'linkedin_url', 'portfolio_url', 'phone', 'is_public',
        ])->filter(fn ($value) => $value !== null)->toArray());

        if (array_key_exists('skill_ids', $data)) {
            $this->syncSkills($profile, $data['skill_ids']);
        }

        $profile = $profile->fresh(['user', 'category', 'skills.category']);
        $this->syncUserAvatar($profile->user, $profile->profile_photo);

        return $profile;
    }

    public function delete(DeveloperProfile $profile): void
    {
        $this->profiles->delete($profile);
    }

    private function syncSkills(DeveloperProfile $profile, array $skillIds): void
    {
        $sync = [];
        foreach ($skillIds as $skillId) {
            $sync[$skillId] = ['proficiency' => 3];
        }
        $profile->skills()->sync($sync);
    }

    public function uploadCv(User $user, UploadedFile $file): array
    {
        $profile = $user->developerProfile;

        if (! $profile) {
            throw new \RuntimeException('Developer profile not found.');
        }

        $this->deleteStoredCv($profile->cv_path);

        $path = $file->store('cvs/'.$user->id, 'public');
        $url = $this->publicStorageUrl($path);

        $this->profiles->update($profile, [
            'cv_path' => $url,
            'cv_original_name' => $file->getClientOriginalName(),
        ]);

        return [
            'cv_path' => $url,
            'cv_original_name' => $file->getClientOriginalName(),
        ];
    }

    public function uploadPhoto(User $user, UploadedFile $file): string
    {
        $this->deleteStoredPhoto($user->developerProfile?->profile_photo);

        $path = $file->store('profile-photos/'.$user->id, 'public');
        $url = $this->publicStorageUrl($path);

        if ($user->developerProfile) {
            $this->profiles->update($user->developerProfile, ['profile_photo' => $url]);
            $this->syncUserAvatar($user, $url);
        } else {
            $user->update(['avatar' => $url]);
        }

        return $url;
    }

    private function syncUserAvatar(User $user, ?string $profilePhoto): void
    {
        if ($profilePhoto) {
            $user->update(['avatar' => $profilePhoto]);
        }
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    private function deleteStoredCv(?string $cvUrl): void
    {
        if (! $cvUrl) {
            return;
        }

        $relativePath = $this->storageRelativePath($cvUrl);

        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function deleteStoredPhoto(?string $photoUrl): void
    {
        if (! $photoUrl) {
            return;
        }

        $relativePath = $this->storageRelativePath($photoUrl);

        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function storageRelativePath(string $photoUrl): ?string
    {
        if (str_starts_with($photoUrl, '/storage/')) {
            return ltrim(substr($photoUrl, strlen('/storage/')), '/');
        }

        $storagePrefix = rtrim(config('app.url'), '/').'/storage/';
        if (str_starts_with($photoUrl, $storagePrefix)) {
            return ltrim(str_replace($storagePrefix, '', $photoUrl), '/');
        }

        return null;
    }
}
