<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    public const DEFAULT_ERROR_MESSAGE = 'Dados da requisição inválidos.';
    
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof ValidationException) {
            // Log para debug
            error_log('ValidationException caught: ' . json_encode($throwable->validator->errors()->getMessages()));
            
            $data = [
                'status' => 'error',
                'message' => self::DEFAULT_ERROR_MESSAGE,
                'errors' => $throwable->validator->errors()->getMessages()
            ];

            return $response
                ->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream(json_encode($data)));
        }

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
