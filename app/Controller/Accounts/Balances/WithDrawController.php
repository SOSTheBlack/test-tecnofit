<?php

declare(strict_types=1);

namespace App\Controller\Accounts\Balances;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class WithDrawController extends BalanceController
{
    public function __invoke(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $data = $request->all();
        $accountId = $request->route('accountId');
        
        // Implemente a lógica de saque via Pix aqui
        // Exemplo: validação, chamada de serviço, resposta JSON
        
        $result = [
            'status' => 'success',
            'message' => 'Solicitação de saque recebida.',
            'account_id' => $accountId,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $response->json($result);
    }
}