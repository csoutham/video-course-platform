<?php

namespace App\Services\Audit;

use App\Models\AuditLog;

class AuditLogService
{
    public function record(string $eventType, ?int $userId = null, array $context = []): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'context' => $context,
        ]);
    }
}
