<?php

namespace App\Exceptions;

use Exception;

class HttpException extends Exception
{
    public function __construct(private int $statusCode, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $this->defaultMessageForStatus($statusCode));
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    private function defaultMessageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Server Error',
            default => 'HTTP Error',
        };
    }
}
