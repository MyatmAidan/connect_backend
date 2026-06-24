<?php

namespace App\Events;

use App\Models\ConnectionRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public ConnectionRequest $connectionRequest)
    {
    }
}
