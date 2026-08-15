<?php

namespace App\Services;

use App\Models\ActivityLogModel;

class ActivityLogService
{
    public function record(?int $userId, string $action, string $module, ?int $entityId, string $description, ?string $ipAddress = null): void
    {
        (new ActivityLogModel())->insert([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
