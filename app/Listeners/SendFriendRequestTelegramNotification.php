<?php

namespace App\Listeners;

use App\Events\FriendRequestSent;
use App\Services\FriendRequestTelegramService;

class SendFriendRequestTelegramNotification
{
    public function __construct(private readonly FriendRequestTelegramService $friendRequestTelegram)
    {
    }

    public function handle(FriendRequestSent $event): void
    {
        $this->friendRequestTelegram->notifyReceiver($event->connectionRequest);
    }
}
