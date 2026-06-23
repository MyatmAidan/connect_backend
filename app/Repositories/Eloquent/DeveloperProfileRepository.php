<?php

namespace App\Repositories\Eloquent;

use App\Models\DeveloperProfile;
use App\Repositories\Contracts\DeveloperProfileRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class DeveloperProfileRepository extends BaseRepository implements DeveloperProfileRepositoryInterface
{
    public function __construct(DeveloperProfile $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->with(['user', 'skills', 'category']);

        if (! empty($filters['role'])) {
            $role = $this->normalizeRoleFilter((string) $filters['role']);

            $query->where(function (Builder $q) use ($role) {
                $q->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $role))
                    ->orWhere('headline', 'like', '%'.$role.'%');
            });
        }

        if (! empty($filters['skill'])) {
            $query->whereHas('skills', fn (Builder $q) => $q->where('name', 'like', '%'.$filters['skill'].'%')
                ->orWhere('slug', $filters['skill']));
        }

        if (! empty($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }

        if (array_key_exists('is_public', $filters) && $filters['is_public'] !== null) {
            $query->where('is_public', (bool) $filters['is_public']);
        } elseif (! array_key_exists('is_public', $filters)) {
            $query->where('is_public', true);
        }

        return $query->latest();
    }

    private function normalizeRoleFilter(string $role): string
    {
        return match ($role) {
            'fullstack' => 'full-stack',
            default => $role,
        };
    }
}
