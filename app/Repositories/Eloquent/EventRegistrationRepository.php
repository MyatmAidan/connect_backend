<?php

namespace App\Repositories\Eloquent;

use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EventRegistrationRepository extends BaseRepository implements EventRegistrationRepositoryInterface
{
    public function __construct(EventRegistration $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->with(['user', 'reviewer', 'event']);

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at');
    }
}
