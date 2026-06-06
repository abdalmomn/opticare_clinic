<?php

namespace App\Modules\Core\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(
        int $statusCode,
        string $message,
        protected ?string $errorCode = null,
        protected mixed $errors = null
    ) {
        parent::__construct($statusCode, $message);
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }
}
