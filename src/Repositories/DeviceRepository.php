<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class DeviceRepository
{
    public function all(): array
    {
        $stmt = Database::instance()->pdo()->query('
            SELECT id, name, host, api_port, use_ssl, username, created_at, updated_at
            FROM devices
            ORDER BY id DESC
        ');

        return $stmt->fetchAll();
    }

    public function findWithSecret(int $id): ?array
    {
        $stmt = Database::instance()->pdo()->prepare('
            SELECT id, name, host, api_port, use_ssl, username, password_ciphertext, created_at, updated_at
            FROM devices
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = Database::instance()->pdo()->prepare('
            INSERT INTO devices (name, host, api_port, use_ssl, username, password_ciphertext)
            VALUES (:name, :host, :api_port, :use_ssl, :username, :password_ciphertext)
            RETURNING id
        ');
        $stmt->execute([
            'name' => $data['name'],
            'host' => $data['host'],
            'api_port' => $data['api_port'] ?? 8728,
            'use_ssl' => $data['use_ssl'] ?? false,
            'username' => $data['username'],
            'password_ciphertext' => $data['password_ciphertext'],
        ]);

        $id = $stmt->fetchColumn();
        return (int) $id;
    }
}
