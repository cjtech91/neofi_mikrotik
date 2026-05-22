<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class AuditRepository
{
    public function log(?int $deviceId, string $action, array $payload = []): void
    {
        $stmt = Database::instance()->pdo()->prepare('
            INSERT INTO audits (device_id, action, payload)
            VALUES (:device_id, :action, :payload::jsonb)
        ');

        $stmt->execute([
            'device_id' => $deviceId,
            'action' => $action,
            'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}',
        ]);
    }
}
