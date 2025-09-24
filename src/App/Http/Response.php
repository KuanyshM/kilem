<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(
        private string $body,
        private int $status = 200,
        private array $headers = []
    ) {
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), $status, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    public static function file(string $content, string $mime, int $status = 200): self
    {
        return new self($content, $status, [
            'Content-Type' => $mime,
        ]);
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
