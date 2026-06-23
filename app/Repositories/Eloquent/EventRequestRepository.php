<?php

namespace App\Repositories\Eloquent;

use App\Models\EventRequest;
use App\Repositories\Contracts\EventRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EventRequestRepository extends BaseRepository implements EventRequestRepositoryInterface
{
    public function __construct(EventRequest $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->with(['user', 'reviewer', 'event']);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at');
    }
}
