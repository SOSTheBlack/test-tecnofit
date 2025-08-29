<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\Handler\ValidationExceptionHandler;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @deprecated
 */
class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected ResponseInterface $response
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): PsrResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $exception) {
            return $this->response->json([
                'status' => 'error',
                'message' => ValidationExceptionHandler::VALIDATION_DEFAULT_MESSAGE,
                'errors' => $exception->errors()
            ])->withStatus(422);
        } catch (\Throwable $exception) {
            if (env('APP_DEBUG', false)) {
                error_log("Validation Middleware Error: " . $exception->getMessage());
                error_log("Trace: " . $exception->getTraceAsString());
            }
            
            throw $exception;
        }
    }
}
