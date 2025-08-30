<?php

declare(strict_types=1);

namespace App\Controller\Account\Balance;

use App\DTO\Account\Balance\WithdrawRequestDTO;
use App\Request\WithdrawRequest;
use App\UseCase\Account\Balance\WithdrawUseCase;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class WithDrawController extends BalanceController
{
    public function __construct(
        private ResponseInterface $response,
    ) {}

    public function __invoke(string $accountId, WithdrawRequest $request): PsrResponseInterface
    {
        try {
            // Criar DTO a partir dos dados validados
            $requestData = array_merge($request->validated(), ['account_id' => $accountId]);
            $withdrawRequestData = WithdrawRequestDTO::fromRequestData($requestData);

            $result = (new WithdrawUseCase())->execute($withdrawRequestData);

            // Retornar resposta baseada no resultado
            return $this->response->json($result->toJsonResponse())->withStatus($result->getHttpStatusCode());
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
