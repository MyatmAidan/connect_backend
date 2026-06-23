<?php

namespace App\Services;

use App\Enums\ConnectionRequestStatus;
use App\Enums\JobApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserStatus;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function stats(): array
    {
        return [
            'total_users' => User::query()->count(),
            'active_users' => User::query()->where('status', UserStatus::Active->value)->count(),
            'banned_users' => User::query()->where('status', UserStatus::Banned->value)->count(),
            'total_connections' => Connection::query()->count(),
            'pending_requests' => ConnectionRequest::query()
                ->where('status', ConnectionRequestStatus::Pending->value)->count(),
            'total_events' => Event::query()->count(),
            'telegram_connected_users' => User::query()->whereNotNull('telegram_chat_id')->count(),
            'total_jobs' => Job::query()->count(),
            'open_jobs' => Job::query()->where('status', JobStatus::Open->value)->count(),
            'total_applications' => JobApplication::query()->count(),
            'pending_applications' => JobApplication::query()
                ->where('status', JobApplicationStatus::Pending->value)
                ->count(),
        ];
    }

    public function charts(): array
    {
        return [
            'jobs_by_status' => Job::query()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->map(fn ($count, $status) => ['status' => (string) $status, 'count' => (int) $count])
                ->values()
                ->all(),
            'applications_by_status' => JobApplication::query()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->map(fn ($count, $status) => ['status' => (string) $status, 'count' => (int) $count])
                ->values()
                ->all(),
        ];
    }

    public function userGrowth(): array
    {
        return User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function activity(): array
    {
        return [
            'new_users_7d' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'new_connections_7d' => Connection::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'new_events_7d' => Event::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }
}
