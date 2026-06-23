<?php

namespace App\Models;

use App\Enums\EventRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRequest extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'title',
        'section',
        'event_date',
        'photo',
        'meeting_url',
        'message',
        'status',
        'reviewed_by',
        'reviewed_at',
        'event_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventRequestStatus::class,
            'event_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
