<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DeveloperSkill extends Pivot
{
    use HasUlids;

    protected $table = 'developer_skills';

    public $incrementing = false;

    protected $fillable = [
        'developer_profile_id',
        'skill_id',
        'proficiency',
    ];

    protected function casts(): array
    {
        return [
            'proficiency' => 'integer',
        ];
    }
}
