<?php

declare(strict_types=1);

namespace App\Controller\Account\Balance;

use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\Request\WithdrawRequest;
use App\UseCase\Account\Balance\WithdrawUseCase;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class WithdrawController extends BalanceController
{
    public function __construct(private ResponseInterface $response, private WithdrawUseCase $withdrawUseCase)
    {
        
    }

    public function __invoke(string $accountId, WithdrawRequest $request): PsrResponseInterface
    {
        try {
            $requestData = array_merge($request->validated(), ['account_id' => $accountId]);
            $withdrawRequestData = WithdrawRequestData::fromRequest($requestData);

            $result = $this->withdrawUseCase->execute($withdrawRequestData);

            // Retornar resposta baseada no resultado
            return $this->response
                ->json($result->toJsonResponse())
                ->withStatus($result->getHttpStatusCode()
            );
        } catch (\Throwable $e) {
            error_log('Erro ao processar solicitação de saque: ' . print_r($e, false));
            
            return $this->response->json([
                'success' => false,
                'message' => 'Erro interno do servidor.',
                'error_code' => 'INTERNAL_ERROR',
                'errors' => [$e->getMessage()]
            ])->withStatus(500);
        }
    }
}
