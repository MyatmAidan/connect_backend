<?php

namespace App\Repositories\Eloquent;

use App\Models\Connection;
use App\Repositories\Contracts\ConnectionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ConnectionRepository extends BaseRepository implements ConnectionRepositoryInterface
{
    public function __construct(Connection $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->with([
            'userOne.developerProfile',
            'userTwo.developerProfile',
            'conversation',
        ]);

        if (! empty($filters['user_id'])) {
            $userId = $filters['user_id'];
            $query->where(function (Builder $q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            });
        }

        return $query->latest();
    }
}
