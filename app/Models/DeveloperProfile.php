<?php

namespace App\Models;

use App\Enums\ExperienceLevel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeveloperProfile extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'category_id',
        'company_id',
        'profile_photo',
        'headline',
        'bio',
        'experience_level',
        'location',
        'github_url',
        'linkedin_url',
        'portfolio_url',
        'phone',
        'cv_path',
        'cv_original_name',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'experience_level' => ExperienceLevel::class,
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'developer_skills')
            ->using(DeveloperSkill::class)
            ->withPivot(['id', 'proficiency'])
            ->withTimestamps();
    }
}
