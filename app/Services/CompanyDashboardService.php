<?php

namespace App\Services;

use App\Enums\JobApplicationStatus;
use App\Enums\JobStatus;
use App\Models\CompanyProfile;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Collection;

class CompanyDashboardService
{
    public function stats(CompanyProfile $company): array
    {
        $jobIds = Job::query()
            ->where('company_profile_id', $company->id)
            ->pluck('id');

        $applicationsQuery = JobApplication::query()
            ->whereIn('job_posting_id', $jobIds);

        return [
            'total_jobs' => $jobIds->count(),
            'open_jobs' => Job::query()
                ->where('company_profile_id', $company->id)
                ->where('status', JobStatus::Open->value)
                ->count(),
            'draft_jobs' => Job::query()
                ->where('company_profile_id', $company->id)
                ->where('status', JobStatus::Draft->value)
                ->count(),
            'total_applications' => (clone $applicationsQuery)->count(),
            'pending_applications' => (clone $applicationsQuery)
                ->where('status', JobApplicationStatus::Pending->value)
                ->count(),
            'shortlisted_applications' => (clone $applicationsQuery)
                ->where('status', JobApplicationStatus::Shortlisted->value)
                ->count(),
            'jobs_by_status' => $this->jobsByStatus($company),
            'applications_by_status' => $this->applicationsByStatus($jobIds),
            'applications_7d' => $this->applicationsTrend($jobIds, 7),
            'recent_applications' => $this->recentApplications($jobIds),
        ];
    }

    private function jobsByStatus(CompanyProfile $company): array
    {
        return Job::query()
            ->where('company_profile_id', $company->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn($count, $status) => ['status' => (string) $status, 'count' => (int) $count])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, string>  $jobIds
     */
    private function applicationsByStatus(Collection $jobIds): array
    {
        if ($jobIds->isEmpty()) {
            return [];
        }

        return JobApplication::query()
            ->whereIn('job_posting_id', $jobIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn($count, $status) => ['status' => (string) $status, 'count' => (int) $count])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, string>  $jobIds
     */
    private function applicationsTrend(Collection $jobIds, int $days): array
    {
        if ($jobIds->isEmpty()) {
            return $this->emptyDateSeries($days);
        }

        $counts = JobApplication::query()
            ->whereIn('job_posting_id', $jobIds)
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return $this->fillDateSeries($counts, $days);
    }

    /**
     * @param  Collection<int, string>  $jobIds
     */
    private function recentApplications(Collection $jobIds): array
    {
        if ($jobIds->isEmpty()) {
            return [];
        }

        return JobApplication::query()
            ->with(['job:id,title', 'applicant:id,name'])
            ->whereIn('job_posting_id', $jobIds)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(JobApplication $application) => [
                'id' => $application->id,
                'applicant_name' => $application->applicant?->name,
                'job_title' => $application->job?->title,
                'status' => $application->status instanceof JobApplicationStatus
                    ? $application->status->value
                    : (string) $application->status,
                'created_at' => $application->created_at,
            ])
            ->all();
    }

    private function emptyDateSeries(int $days): array
    {
        return $this->fillDateSeries(collect(), $days);
    }

    private function fillDateSeries(Collection $counts, int $days): array
    {
        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $series[] = [
                'date' => $date,
                'count' => (int) ($counts[$date] ?? 0),
            ];
        }

        return $series;
    }
}
