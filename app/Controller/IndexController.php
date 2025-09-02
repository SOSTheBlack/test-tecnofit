<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class IndexController
{
    public function index(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $data = [
            'message' => 'Tecnofit Pix API',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'account' => '/account/*',
                'health' => '/health',
            ],
        ];

        return $response->json($data);
    }

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
            // Verificação real da conexão com banco
            \Hyperf\DbConnection\Db::select('SELECT 1');

            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            // Verificação real da conexão com Redis
            $redis = \Hyperf\Context\ApplicationContext::getContainer()->get(\Hyperf\Redis\Redis::class);
            $redis->ping();

            return ['status' => 'ok', 'message' => 'Redis connection successful'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
