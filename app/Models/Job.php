<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasUlids;

    protected $table = 'job_postings';

    protected $fillable = [
        'company_profile_id',
        'category_id',
        'title',
        'description',
        'requirements',
        'employment_type',
        'experience_level',
        'location',
        'salary_min',
        'salary_max',
        'salary_currency',
        'status',
        'published_at',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'experience_level' => ExperienceLevel::class,
            'status' => JobStatus::class,
            'published_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_posting_id');
    }
}
