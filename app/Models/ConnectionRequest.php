<?php

namespace App\Models;

use App\Enums\ConnectionRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectionRequest extends Model
{
    use HasUlids;

    protected $fillable = ['sender_id', 'receiver_id', 'message', 'status'];

    protected function casts(): array
    {
        return [
            'status' => ConnectionRequestStatus::class,
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
