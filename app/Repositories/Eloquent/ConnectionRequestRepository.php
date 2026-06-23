<?php

namespace App\Repositories\Eloquent;

use App\Models\ConnectionRequest;
use App\Repositories\Contracts\ConnectionRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ConnectionRequestRepository extends BaseRepository implements ConnectionRequestRepositoryInterface
{
    public function __construct(ConnectionRequest $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->with(['sender', 'receiver']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }

        if (! empty($filters['receiver_id'])) {
            $query->where('receiver_id', $filters['receiver_id']);
        }

        return $query->latest();
    }
}
