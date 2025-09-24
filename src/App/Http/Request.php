<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private array $params = [];

    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $body,
        private array $files,
        private array $headers,
        private string $rawBody
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $query = $_GET;
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $rawBody = file_get_contents('php://input') ?: '';
        $body = $_POST;

        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
        if (str_contains($contentType, 'application/json') && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($method, $path, $query, $body, $_FILES, $headers, $rawBody);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = array_change_key_case($this->headers, CASE_LOWER);
        return $normalized[strtolower($key)] ?? $default;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
}
