<?php

declare(strict_types=1);

namespace App\Controller\Accounts\Balances;

use App\DTO\Account\Balance\WithdrawRequestDTO;
use App\Service\AccountService;
use App\Request\WithdrawRequest;
use App\UseCase\Account\Balance\WithdrawUseCase;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class WithDrawController extends BalanceController
{
    public function __construct(
        private ResponseInterface $response,
        private AccountService $accountService,
    ) {}

    public function __invoke(string $accountId, WithdrawRequest $request): PsrResponseInterface
    {
        try {
            // Criar DTO a partir dos dados validados
            $requestData = array_merge($request->validated(), ['account_id' => $accountId]);
            $withdrawRequestData = WithdrawRequestDTO::fromRequestData($requestData);

            $result = (new WithdrawUseCase($this->accountService))->execute($withdrawRequestData);

            // Processar saque via service
            // $result = $this->accountService->processWithdraw($withdrawRequestData);

            // Retornar resposta baseada no resultado
            return $this->response->json($result->toJsonResponse())->withStatus($result->getHttpStatusCode());
            
        } catch (\Exception $e) {
            return $this->response->json([
                'success' => false,
                'message' => 'Erro interno do servidor.',
                'error_code' => 'INTERNAL_ERROR',
                'errors' => [$e->getMessage()]
            ])->withStatus(500);
        }
    }
}
