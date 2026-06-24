<?php

namespace App\Services;

use App\Models\DeveloperProfile;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TrustScoreService
{
    /**
     * @return array{
     *     score: int,
     *     level: string,
     *     badges: list<string>,
     *     fields: array<string, string|null>
     * }
     */
    public function forUser(User $user): array
    {
        $user->loadMissing(['developerProfile.skills']);

        $profile = $user->developerProfile;
        $fields = $this->collectFields($user, $profile);

        $score = 0;

        if ($this->isFilled($fields['github_url'])) {
            $score += 20;
        }

        if ($this->isFilled($fields['portfolio_url'])) {
            $score += 15;
        }

        if ($this->isFilled($fields['role'])) {
            $score += 10;
        }

        if ($this->isFilled($fields['skills'])) {
            $score += 10;
        }

        $score = min(100, max(0, $score));

        return [
            'score' => $score,
            'level' => $this->resolveLevel($score),
            'badges' => $this->buildBadges($fields),
            'fields' => $fields,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function collectFields(User $user, ?DeveloperProfile $profile): array
    {
        return [
            'github_url' => $this->optionalProfileField($profile, 'github_url'),
            'portfolio_url' => $this->optionalProfileField($profile, 'portfolio_url'),
            'role' => $this->resolveRoleLabel($user, $profile),
            'skills' => $this->resolveSkillsLabel($profile),
            'experience' => $this->resolveExperienceLabel($profile),
        ];
    }

    private function resolveRoleLabel(User $user, ?DeveloperProfile $profile): ?string
    {
        $headline = $this->optionalProfileField($profile, 'headline');
        if ($this->isFilled($headline)) {
            return $headline;
        }

        if (Schema::hasColumn('users', 'role') && filled($user->role)) {
            return is_object($user->role) && property_exists($user->role, 'value')
                ? (string) $user->role->value
                : (string) $user->role;
        }

        return null;
    }

    private function resolveExperienceLabel(?DeveloperProfile $profile): ?string
    {
        if (! $profile) {
            return null;
        }

        if (Schema::hasColumn('developer_profiles', 'experience_level') && filled($profile->experience_level)) {
            return is_object($profile->experience_level) && property_exists($profile->experience_level, 'value')
                ? (string) $profile->experience_level->value
                : (string) $profile->experience_level;
        }

        return $this->optionalProfileField($profile, 'experience');
    }

    private function resolveSkillsLabel(?DeveloperProfile $profile): ?string
    {
        if (! $profile || ! $profile->relationLoaded('skills')) {
            return null;
        }

        $names = $profile->skills
            ->pluck('name')
            ->filter(fn ($name) => filled($name))
            ->values();

        if ($names->isEmpty()) {
            return null;
        }

        return $names->take(8)->implode(', ');
    }

  /**
     * @param  array<string, string|null>  $fields
     * @return list<string>
     */
    private function buildBadges(array $fields): array
    {
        $badges = [];

        if ($this->isFilled($fields['github_url'])) {
            $badges[] = 'GitHub Verified';
        }

        if ($this->isFilled($fields['portfolio_url'])) {
            $badges[] = 'Portfolio Available';
        }

        if ($this->isFilled($fields['skills'])) {
            $badges[] = 'Skilled Developer';
        }

        return $badges;
    }

    private function resolveLevel(int $score): string
    {
        if ($score > 70) {
            return 'HIGH';
        }

        if ($score >= 40) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    private function optionalProfileField(?DeveloperProfile $profile, string $column): ?string
    {
        if (! $profile || ! Schema::hasColumn('developer_profiles', $column)) {
            return null;
        }

        $value = $profile->getAttribute($column);

        return $this->isFilled($value) ? (string) $value : null;
    }

    private function isFilled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return filled($value);
    }
}
