<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TelegramUpdateDeduplicator
{
    /**
     * Returns true when this update was already handled (duplicate delivery).
     */
    public function alreadyProcessed(?int $updateId): bool
    {
        if ($updateId === null) {
            return false;
        }

        $key = 'telegram:update:'.$updateId;

        return ! Cache::add($key, true, now()->addDay());
    }
}
