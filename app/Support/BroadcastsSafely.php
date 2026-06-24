<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class BroadcastsSafely
{
    public static function toOthers(object $event): void
    {
        $connection = config('broadcasting.default');

        if (in_array($connection, ['null', 'log'], true)) {
            return;
        }

        try {
            broadcast($event)->toOthers();
        } catch (\Throwable $exception) {
            Log::warning('Broadcast failed — start Reverb with `php artisan reverb:start`', [
                'event' => $event::class,
                'connection' => $connection,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
