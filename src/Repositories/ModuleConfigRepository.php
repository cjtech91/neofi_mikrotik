<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class ModuleConfigRepository
{
    /** @return array<string, mixed> */
    public function get(string $module): array
    {
        $stmt = Database::instance()->pdo()->prepare('
            SELECT config
            FROM module_configs
            WHERE module = :module
            LIMIT 1
        ');
        $stmt->execute(['module' => $module]);
        $row = $stmt->fetch();
        if (!is_array($row) || !isset($row['config'])) {
            return [];
        }

        $decoded = json_decode((string) $row['config'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $config */
    public function set(string $module, array $config): void
    {
        $payload = json_encode($config, JSON_UNESCAPED_SLASHES) ?: '{}';
        $stmt = Database::instance()->pdo()->prepare('
            INSERT INTO module_configs (module, config, updated_at)
            VALUES (:module, :config::jsonb, NOW())
            ON CONFLICT (module)
            DO UPDATE SET config = EXCLUDED.config, updated_at = NOW()
        ');
        $stmt->execute([
            'module' => $module,
            'config' => $payload,
        ]);
    }
}
