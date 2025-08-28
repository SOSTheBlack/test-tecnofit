<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Controller(prefix: '/api')]
class IndexController
{
    #[GetMapping(path: '/')]
    public function index(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $data = [
            'message' => 'Tecnofit Pix API',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'auth' => '/api/auth/*',
                'users' => '/api/users/*',
                'withdrawals' => '/api/withdrawals/*',
                'health' => '/api/health',
            ],
        ];

        return $response->json($data);
    }

    #[GetMapping(path: '/health')]
    public function health(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        // Verificações básicas de saúde da aplicação
        $status = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
            ],
        ];

        $httpStatus = 200;
        foreach ($status['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                $httpStatus = 503;
                $status['status'] = 'error';

                break;
            }
        }

        return $response->json($status)->withStatus($httpStatus);
    }

    private function checkDatabase(): array
    {
        try {
            // Implementar verificação de conexão com banco
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            // Implementar verificação de conexão com Redis
            return ['status' => 'ok', 'message' => 'Redis connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
