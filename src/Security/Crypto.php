<?php

declare(strict_types=1);

namespace App\Security;

final class Crypto
{
    public static function encrypt(string $plaintext): string
    {
        $key = self::keyBytes();
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode(json_encode([
            'v' => 1,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ciphertext),
        ], JSON_UNESCAPED_SLASHES) ?: '');
    }

    public static function decrypt(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid ciphertext');
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload) || !isset($payload['iv'], $payload['tag'], $payload['ct'])) {
            throw new \RuntimeException('Invalid ciphertext payload');
        }

        $iv = base64_decode((string) $payload['iv'], true);
        $tag = base64_decode((string) $payload['tag'], true);
        $ct = base64_decode((string) $payload['ct'], true);
        if ($iv === false || $tag === false || $ct === false) {
            throw new \RuntimeException('Invalid ciphertext payload');
        }

        $key = self::keyBytes();
        $plaintext = openssl_decrypt(
            $ct,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            ''
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plaintext;
    }

    private static function keyBytes(): string
    {
        $raw = (string) getenv('APP_KEY');
        if ($raw === '') {
            throw new \RuntimeException('APP_KEY is required');
        }

        $bytes = hash('sha256', $raw, true);
        if ($bytes === false || strlen($bytes) !== 32) {
            throw new \RuntimeException('APP_KEY derivation failed');
        }

        return $bytes;
    }
}
