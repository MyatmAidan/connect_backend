<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasUlids;

    protected $fillable = [
        'created_by',
        'title',
        'section',
        'event_date',
        'photo',
        'meeting_url',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function isRegistrationOpen(): bool
    {
        if (! $this->event_date) {
            return true;
        }

        return $this->event_date->startOfDay()->gte(now()->startOfDay());
    }
}
