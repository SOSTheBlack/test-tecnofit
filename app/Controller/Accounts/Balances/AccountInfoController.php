<?php

declare(strict_types=1);

namespace App\Controller\Accounts\Balances;

use App\DTO\Account\Balance\AccountDataDTO;
use App\DTO\Account\Balance\AccountSummaryDTO;
use App\Service\AccountService;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class AccountInfoController extends BalanceController
{
    public function __construct(
        private ResponseInterface $response,
        private AccountService $accountService,
    ) {}

    /**
     * Obtém informações detalhadas da conta
     */
    public function getAccountInfo(string $accountId): PsrResponseInterface
    {
        $accountData = $this->accountService->getAccountData($accountId);
        
        if (!$accountData) {
            return $this->response->json([
                'success' => false,
                'message' => 'Conta não encontrada.',
                'error_code' => 'ACCOUNT_NOT_FOUND'
            ])->withStatus(404);
        }

        return $this->response->json([
            'success' => true,
            'message' => 'Informações da conta obtidas com sucesso.',
            'data' => $accountData->toArray()
        ]);
    }

    /**
     * Obtém resumo da conta
     */
    public function getAccountSummary(string $accountId): PsrResponseInterface
    {
        $account = $this->accountService->findAccountById($accountId);
        
        if (!$account) {
            return $this->response->json([
                'success' => false,
                'message' => 'Conta não encontrada.',
                'error_code' => 'ACCOUNT_NOT_FOUND'
            ])->withStatus(404);
        }

        $summary = AccountSummaryDTO::fromModel($account);

        return $this->response->json([
            'success' => true,
            'message' => 'Resumo da conta obtido com sucesso.',
            'data' => $summary->toArray()
        ]);
    }

    /**
     * Verifica se a conta tem saldo suficiente para um valor
     */
    public function checkBalance(string $accountId, float $amount): PsrResponseInterface
    {
        $accountData = $this->accountService->getAccountData($accountId);
        
        if (!$accountData) {
            return $this->response->json([
                'success' => false,
                'message' => 'Conta não encontrada.',
                'error_code' => 'ACCOUNT_NOT_FOUND'
            ])->withStatus(404);
        }

        $canWithdraw = $accountData->canWithdraw($amount);

        return $this->response->json([
            'success' => true,
            'message' => $canWithdraw ? 'Saldo suficiente.' : 'Saldo insuficiente.',
            'data' => [
                'account_id' => $accountData->id,
                'requested_amount' => $amount,
                'available_balance' => $accountData->availableBalance,
                'can_withdraw' => $canWithdraw,
                'missing_amount' => $canWithdraw ? 0 : ($amount - $accountData->availableBalance),
            ]
        ]);
    }
}
