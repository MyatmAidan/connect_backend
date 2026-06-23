<?php

namespace App\Services;

use App\Enums\JobApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class JobApplicationService
{
    private const INTERVIEW_ACK_MESSAGE = 'Thank you for your Cv.I will contact you again if you are selected for an interview.';

    public function __construct(private readonly TelegramNotificationService $telegramNotifications) {}

    public function apply(User $user, Job $job, ?string $coverLetter = null): JobApplication
    {

        if ($job->status !== JobStatus::Open) {
            throw ValidationException::withMessages([
                'job' => ['This job is not accepting applications.'],
            ]);
        }

        $profile = $user->developerProfile;

        if (! $profile) {
            throw ValidationException::withMessages([
                'profile' => ['Create your developer profile before applying.'],
            ]);
        }

        if (! $profile->phone) {
            throw ValidationException::withMessages([
                'phone' => ['Add your phone number to your profile before applying.'],
            ]);
        }

        if (! $profile->cv_path) {
            throw ValidationException::withMessages([
                'cv' => ['Upload your CV to your profile before applying.'],
            ]);
        }

        if (! $user->telegram_chat_id) {
            throw ValidationException::withMessages([
                'telegram' => ['Connect Telegram in Settings before applying for jobs.'],
            ]);
        }

        if (JobApplication::query()->where('job_posting_id', $job->id)->where('applicant_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'job' => ['You have already applied for this job.'],
            ]);
        }

        return JobApplication::query()->create([
            'job_posting_id' => $job->id,
            'applicant_id' => $user->id,
            'cover_letter' => $coverLetter,
            'status' => JobApplicationStatus::Pending,
        ]);
    }

    public function myApplications(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->jobApplications()
            ->with(['job.companyProfile'])
            ->latest()
            ->paginate($perPage);
    }

    public function listForCompany(CompanyProfile $company, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = JobApplication::query()
            ->with([
                'job',
                'applicant.developerProfile.skills',
            ])
            ->whereHas('job', fn($q) => $q->where('company_profile_id', $company->id))
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['job_id'])) {
            $query->where('job_posting_id', $filters['job_id']);
        }

        return $query->paginate($perPage);
    }

    public function listAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = JobApplication::query()
            ->with(['job.companyProfile', 'applicant.developerProfile'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['job_id'])) {
            $query->where('job_posting_id', $filters['job_id']);
        }

        if (! empty($filters['company_profile_id'])) {
            $query->whereHas('job', fn($q) => $q->where('company_profile_id', $filters['company_profile_id']));
        }

        return $query->paginate($perPage);
    }

    public function show(JobApplication $application): JobApplication
    {
        return $application->load([
            'job.companyProfile.company',
            'applicant.developerProfile.skills',
        ]);
    }

    public function updateStatus(JobApplication $application, string $status, ?string $notes = null): JobApplication
    {
        $application->update([
            'status' => $status,
            'company_notes' => $notes ?? $application->company_notes,
            'reviewed_at' => now(),
        ]);

        return $this->show($application);
    }

    public function sendInterviewAcknowledgment(JobApplication $application): JobApplication
    {
        $application->loadMissing('applicant', 'job.companyProfile');
        $applicant = $application->applicant;

        if (! $applicant?->telegram_chat_id) {
            throw ValidationException::withMessages([
                'telegram' => ['This applicant has not connected Telegram.'],
            ]);
        }

        if ($application->interview_ack_sent_at) {
            throw ValidationException::withMessages([
                'application' => ['Interview acknowledgment was already sent.'],
            ]);
        }

        if (! $this->telegramNotifications->isConfigured()) {
            throw ValidationException::withMessages([
                'telegram' => ['Telegram bot is not configured.'],
            ]);
        }

        $companyName = $application->job?->companyProfile?->company_name ?? 'The company';
        $jobTitle = $application->job?->title ?? 'your application';

        $log = $this->telegramNotifications->sendToUser(
            $applicant,
            "{$companyName} — {$jobTitle}",
            self::INTERVIEW_ACK_MESSAGE,
            'job_interview_ack',
        );

        if ($log->status !== 'sent') {
            throw ValidationException::withMessages([
                'telegram' => [$log->error_message ?? 'Could not send Telegram message.'],
            ]);
        }

        $application->update([
            'interview_ack_sent_at' => now(),
            'status' => JobApplicationStatus::Shortlisted,
            'reviewed_at' => now(),
        ]);

        return $this->show($application);
    }

    public function withdraw(User $user, JobApplication $application): JobApplication
    {
        if ($application->applicant_id !== $user->id) {
            throw ValidationException::withMessages([
                'application' => ['You can only withdraw your own applications.'],
            ]);
        }

        if ($application->status === JobApplicationStatus::Accepted) {
            throw ValidationException::withMessages([
                'application' => ['Accepted applications cannot be withdrawn.'],
            ]);
        }

        $application->update(['status' => JobApplicationStatus::Withdrawn]);

        return $this->show($application);
    }
}
