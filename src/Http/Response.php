<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    private function __construct(
        private readonly int $status,
        private readonly array $headers,
        private readonly string $body,
    ) {
    }

    public static function json(array $payload, int $status = 200): self
    {
        return new self(
            $status,
            ['content-type' => 'application/json; charset=utf-8'],
            json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}',
        );
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self(
            $status,
            ['content-type' => 'text/html; charset=utf-8'],
            $html,
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self(
            $status,
            ['location' => $location],
            '',
        );
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
