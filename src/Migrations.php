<?php

declare(strict_types=1);

namespace App;

final class Migrations
{
    public static function run(): void
    {
        $pdo = Database::instance()->pdo();

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS devices (
                id BIGSERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                host TEXT NOT NULL,
                api_port INTEGER NOT NULL DEFAULT 8728,
                use_ssl BOOLEAN NOT NULL DEFAULT FALSE,
                username TEXT NOT NULL,
                password_ciphertext TEXT NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS audits (
                id BIGSERIAL PRIMARY KEY,
                device_id BIGINT REFERENCES devices(id) ON DELETE SET NULL,
                action TEXT NOT NULL,
                payload JSONB NOT NULL DEFAULT \'{}\',
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );
        ');

        $pdo->exec('
            CREATE INDEX IF NOT EXISTS audits_device_id_created_at_idx
            ON audits (device_id, created_at DESC);
        ');
    }
}
