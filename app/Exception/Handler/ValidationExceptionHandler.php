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
    public const VALIDATION_DEFAULT_MESSAGE = 'Dados da requisição inválidos.';
    
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        if ($throwable instanceof ValidationException) {
            // Log para debug
            error_log('ValidationException caught: ' . json_encode($throwable->validator->errors()->getMessages()));
            
            $data = [
                'status' => 'error',
                'message' => self::VALIDATION_DEFAULT_MESSAGE,
                'errors' => $throwable->validator->errors()->getMessages()
            ];

            $jsonContent = json_encode($data);
            if ($jsonContent === false) {
                $jsonContent = '{"success":false,"message":"JSON encoding error"}';
            }
            
            return $response
                ->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream($jsonContent));
        }

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
