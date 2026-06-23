<?php

namespace App\Repositories\Eloquent;

use App\Models\AdminLog;
use App\Repositories\Contracts\AdminLogRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class AdminLogRepository extends BaseRepository implements AdminLogRepositoryInterface
{
    public function __construct(AdminLog $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->with('admin');

        if (! empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $query->latest();
    }
}
