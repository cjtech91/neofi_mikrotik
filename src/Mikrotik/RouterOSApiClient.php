<?php

declare(strict_types=1);

namespace App\Mikrotik;

final class RouterOSApiClient
{
    private $socket = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 8728,
        private readonly bool $useSsl = false,
        private readonly int $timeoutSeconds = 6,
    ) {
    }

    public function connect(): void
    {
        $scheme = $this->useSsl ? 'tls' : 'tcp';
        $remote = $scheme . '://' . $this->host . ':' . $this->port;

        $context = null;
        if ($this->useSsl) {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
        }

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context ?: stream_context_get_default()
        );

        if ($socket === false) {
            throw new \RuntimeException('Unable to connect to RouterOS API: ' . $errstr);
        }

        stream_set_timeout($socket, $this->timeoutSeconds);
        $this->socket = $socket;
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
    }

    public function login(string $username, string $password): void
    {
        $this->ensureConnected();

        $responses = $this->command('/login', [
            '=name=' . $username,
            '=password=' . $password,
        ]);

        if ($this->containsTrap($responses)) {
            $challenge = $this->loginChallengeToken();
            $response = $this->challengeResponse($challenge, $password);

            $responses = $this->command('/login', [
                '=name=' . $username,
                '=response=' . $response,
            ]);

            if ($this->containsTrap($responses)) {
                throw new \RuntimeException('RouterOS login failed');
            }

            return;
        }
    }

    public function command(string $path, array $words = []): array
    {
        $this->ensureConnected();

        $sentence = array_merge([$path], $words);
        $this->writeSentence($sentence);

        $out = [];
        while (true) {
            $resp = $this->readSentence();
            if ($resp === []) {
                continue;
            }

            $type = $resp[0];
            $data = [];
            for ($i = 1; $i < count($resp); $i++) {
                $word = $resp[$i];
                if ($word === '' || $word[0] !== '=') {
                    continue;
                }
                $parts = explode('=', $word, 3);
                if (count($parts) === 3) {
                    $data[$parts[1]] = $parts[2];
                }
            }

            $out[] = ['type' => $type, 'data' => $data];

            if ($type === '!done') {
                break;
            }
            if ($type === '!trap') {
                break;
            }
        }

        return $out;
    }

    public function rows(string $path, array $words = []): array
    {
        $responses = $this->command($path, $words);
        $rows = [];
        foreach ($responses as $r) {
            if ($r['type'] === '!re') {
                $rows[] = $r['data'];
            }
        }
        return $rows;
    }

    public function commandOrThrow(string $path, array $words = []): array
    {
        $responses = $this->command($path, $words);
        foreach ($responses as $r) {
            if ($r['type'] === '!trap') {
                $message = $r['data']['message'] ?? 'RouterOS error';
                throw new \RuntimeException($message);
            }
        }

        $rows = [];
        foreach ($responses as $r) {
            if ($r['type'] === '!re') {
                $rows[] = $r['data'];
            }
        }

        return $rows[0] ?? [];
    }

    private function readSentence(): array
    {
        $this->ensureConnected();

        $words = [];
        while (true) {
            $len = $this->readLength();
            if ($len === 0) {
                break;
            }
            $words[] = $this->readBytes($len);
        }
        return $words;
    }

    private function writeSentence(array $words): void
    {
        foreach ($words as $word) {
            $this->writeLength(strlen($word));
            $this->writeBytes($word);
        }
        $this->writeLength(0);
    }

    private function readLength(): int
    {
        $c = ord($this->readBytes(1));
        if (($c & 0x80) === 0x00) {
            return $c;
        }
        if (($c & 0xC0) === 0x80) {
            $c2 = ord($this->readBytes(1));
            return (($c & 0x3F) << 8) + $c2;
        }
        if (($c & 0xE0) === 0xC0) {
            $c2 = ord($this->readBytes(1));
            $c3 = ord($this->readBytes(1));
            return (($c & 0x1F) << 16) + ($c2 << 8) + $c3;
        }
        if (($c & 0xF0) === 0xE0) {
            $c2 = ord($this->readBytes(1));
            $c3 = ord($this->readBytes(1));
            $c4 = ord($this->readBytes(1));
            return (($c & 0x0F) << 24) + ($c2 << 16) + ($c3 << 8) + $c4;
        }

        $c2 = ord($this->readBytes(1));
        $c3 = ord($this->readBytes(1));
        $c4 = ord($this->readBytes(1));
        $c5 = ord($this->readBytes(1));
        return ($c2 << 24) + ($c3 << 16) + ($c4 << 8) + $c5;
    }

    private function writeLength(int $len): void
    {
        if ($len < 0x80) {
            $this->writeBytes(chr($len));
            return;
        }
        if ($len < 0x4000) {
            $len |= 0x8000;
            $this->writeBytes(chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
            return;
        }
        if ($len < 0x200000) {
            $len |= 0xC00000;
            $this->writeBytes(
                chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF)
            );
            return;
        }
        if ($len < 0x10000000) {
            $len |= 0xE0000000;
            $this->writeBytes(
                chr(($len >> 24) & 0xFF) .
                chr(($len >> 16) & 0xFF) .
                chr(($len >> 8) & 0xFF) .
                chr($len & 0xFF)
            );
            return;
        }

        $this->writeBytes(
            chr(0xF0) .
            chr(($len >> 24) & 0xFF) .
            chr(($len >> 16) & 0xFF) .
            chr(($len >> 8) & 0xFF) .
            chr($len & 0xFF)
        );
    }

    private function readBytes(int $length): string
    {
        $this->ensureConnected();

        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);
                $timedOut = is_array($meta) && ($meta['timed_out'] ?? false);
                throw new \RuntimeException($timedOut ? 'RouterOS API timeout' : 'RouterOS API read failed');
            }
            $data .= $chunk;
        }
        return $data;
    }

    private function writeBytes(string $bytes): void
    {
        $this->ensureConnected();

        $written = 0;
        $len = strlen($bytes);
        while ($written < $len) {
            $n = fwrite($this->socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new \RuntimeException('RouterOS API write failed');
            }
            $written += $n;
        }
    }

    private function ensureConnected(): void
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException('Not connected');
        }
    }

    private function containsTrap(array $responses): bool
    {
        foreach ($responses as $r) {
            if ($r['type'] === '!trap') {
                return true;
            }
        }
        return false;
    }

    private function loginChallengeToken(): string
    {
        $responses = $this->command('/login');
        foreach ($responses as $r) {
            if ($r['type'] === '!done' && isset($r['data']['ret'])) {
                return $r['data']['ret'];
            }
        }
        throw new \RuntimeException('RouterOS login challenge not provided');
    }

    private function challengeResponse(string $challengeHex, string $password): string
    {
        $challengeBytes = hex2bin($challengeHex);
        if ($challengeBytes === false) {
            throw new \RuntimeException('Invalid challenge token');
        }

        $hash = md5("\x00" . $password . $challengeBytes);
        return '00' . $hash;
    }
}
