<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\User;

class AdminLogService
{
    public function log(
        User $admin,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $description = null,
    ): void {
        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
        ]);
    }
}
