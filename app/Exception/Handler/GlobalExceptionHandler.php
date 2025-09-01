<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\MethodNotAllowedHttpException;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Hyperf\HttpMessage\Exception\UnauthorizedHttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GlobalExceptionHandler extends ExceptionHandler
{
    /**
     * Mapeamento de exceções para configurações de resposta
     */
    private const EXCEPTION_MAP = [
        ModelNotFoundException::class => [
            'message' => 'Recurso não encontrado.',
            'status' => Response::HTTP_NOT_FOUND,
            'code' => 404,
        ],
        RepositoryNotFoundException::class => [
            'message' => null, // Usar mensagem da exceção
            'status' => Response::HTTP_NOT_FOUND,
            'code' => null, // Usar código da exceção
        ],
        \InvalidArgumentException::class => [
            'message' => 'Parâmetros inválidos.',
            'status' => Response::HTTP_BAD_REQUEST,
            'code' => 400,
            'include_error' => true,
        ],
        UnauthorizedHttpException::class => [
            'message' => 'Não autorizado.',
            'status' => Response::HTTP_UNAUTHORIZED,
            'code' => 401,
        ],
        NotFoundHttpException::class => [
            'message' => 'Endpoint não encontrado.',
            'status' => Response::HTTP_NOT_FOUND,
            'code' => 404,
        ],
        MethodNotAllowedHttpException::class => [
            'message' => 'Método HTTP não permitido.',
            'status' => Response::HTTP_METHOD_NOT_ALLOWED,
            'code' => 405,
        ],
    ];

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $handledResponse = $this->handleException($throwable, $response);

        if ($handledResponse !== null) {
            return $handledResponse;
        }

        // Se não foi tratado especificamente, continua para o próximo handler
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return true; // Este handler pode tratar qualquer exceção
    }

    /**
     * Trata exceções usando configuração centralizada
     */
    private function handleException(Throwable $throwable, ResponseInterface $response): ?ResponseInterface
    {
        $exceptionClass = get_class($throwable);

        // Verificar se existe configuração para esta exceção
        if (! isset(self::EXCEPTION_MAP[$exceptionClass])) {
            error_log("Unhandled exception: {$exceptionClass} - {$throwable->getMessage()}");

            return null;
        }

        $config = self::EXCEPTION_MAP[$exceptionClass];

        return $this->buildErrorResponse(
            $throwable,
            $response,
            $config['message'] ?? $throwable->getMessage(),
            $config['status'],
            $config['code'] ?? $throwable->getCode(),
            $config['include_error'] ?? false,
        );
    }

    /**
     * Constrói resposta de erro padronizada
     */
    private function buildErrorResponse(
        Throwable $throwable,
        ResponseInterface $response,
        string $message,
        int $status,
        ?int $code = null,
        bool $includeError = false,
    ): ResponseInterface {
        $data = [
            'status' => 'error',
            'message' => $message,
            'errors' => ['general' => $throwable->getPrevious()?->getMessage()],
        ];

        if ($code !== null) {
            $data['code'] = $code;
        }

        if ($includeError) {
            if (! is_array($data)) {
                $data = [];
            }
            if (! isset($data['errors']) || ! is_array($data['errors'])) {
                $data['errors'] = [];
            }
            if (! isset($data['errors']['general']) || ! is_array($data['errors']['general'])) {
                $data['errors']['general'] = [];
            }
            $data['errors']['general'][] = $throwable->getMessage();
        }

        $this->addDebugInfo($data, $throwable);

        return $this->createJsonResponse($response, $data, $status);
    }

    /**
     * Adiciona informações de debug se em ambiente local
     */
    private function addDebugInfo(array &$data, Throwable $throwable): void
    {
        if ($this->isLocalEnvironment()) {
            $data['debug'] = $this->getDebugTrace($throwable);
        }
    }

    /**
     * Cria uma resposta JSON padronizada
     */
    private function createJsonResponse(ResponseInterface $response, array $data, int $statusCode): ResponseInterface
    {
        $jsonContent = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($jsonContent === false) {
            $jsonContent = '{"success":false,"message":"JSON encoding error"}';
        }

        return $response
            ->withStatus($statusCode)
            ->withAddedHeader('content-type', 'application/json')
            ->withBody(new SwooleStream($jsonContent));
    }

    /**
     * Verifica se está em ambiente local
     */
    private function isLocalEnvironment(): bool
    {
        $environment = env('APP_ENV', 'production');

        return in_array($environment, ['local', 'dev', 'development', 'testing']) && env('APP_DEBUG', true);
    }

    /**
     * Obtém informações de debug da exceção
     */
    private function getDebugTrace(Throwable $throwable): array
    {
        return [
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => array_slice($throwable->getTrace(), 0, 10),
        ];
    }
}
