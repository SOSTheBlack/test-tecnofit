<?php

declare(strict_types=1);

namespace App\Repository\Exceptions;

use Throwable;

class RepositoryNotFoundException extends RepositoryException
{
    private const DEFAULT_MESSAGE = 'Registro não encontrada.';
    private const DEFAULT_CODE = 1002;

    /**
     * RepositoryException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = self::DEFAULT_CODE, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
