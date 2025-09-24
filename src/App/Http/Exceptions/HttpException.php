<?php

declare(strict_types=1);

namespace App\Http\Exceptions;

use RuntimeException;

final class HttpException extends RuntimeException
{
    private int $status;
    private array $details;

    public function __construct(int $status, string $message, array $details = [], ?int $code = null)
    {
        parent::__construct($message, $code ?? 0);
        $this->status = $status;
        $this->details = $details;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
