<?php

declare(strict_types=1);

namespace App\Repository\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RepositoryNotFoundException extends RuntimeException
{
    private const DEFAULT_MESSAGE = 'Registro não encontrado.';

    public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = Response::HTTP_NOT_FOUND, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
