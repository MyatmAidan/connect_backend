<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'slug', 'category_id', 'image'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function developerProfiles(): BelongsToMany
    {
        return $this->belongsToMany(DeveloperProfile::class, 'developer_skills')
            ->using(DeveloperSkill::class)
            ->withPivot(['id', 'proficiency'])
            ->withTimestamps();
    }
}
