<?php

namespace App\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUlids;

    protected $fillable = ['slug', 'name_en', 'name_my'];

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function localizedName(?string $locale = 'en'): string
    {
        return match (Locale::resolve($locale)) {
            'my' => $this->name_my,
            default => $this->name_en,
        };
    }
}
