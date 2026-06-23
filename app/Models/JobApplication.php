<?php

namespace App\Models;

use App\Enums\JobApplicationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasUlids;

    protected $fillable = [
        'job_posting_id',
        'applicant_id',
        'cover_letter',
        'status',
        'company_notes',
        'reviewed_at',
        'interview_ack_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobApplicationStatus::class,
            'reviewed_at' => 'datetime',
            'interview_ack_sent_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_posting_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }
}
