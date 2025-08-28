<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Account;
use App\Repository\Contract\AccountRepositoryInterface;

class AccountService
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function findAccountById(string $accountId): ?Account
    {
        return $this->accountRepository->findById($accountId);
    }

    public function getAccountBalance(string $accountId): ?float
    {
        return $this->accountRepository->getBalance($accountId);
    }

    public function hasSufficientBalance(string $accountId, float $amount): bool
    {
        return $this->accountRepository->hasSufficientBalance($accountId, $amount);
    }

    public function processWithdraw(string $accountId, float $amount): array
    {
        // Verifica se a conta existe
        $account = $this->findAccountById($accountId);
        if (!$account) {
            return [
                'success' => false,
                'message' => 'Conta não encontrada.',
                'error_code' => 'ACCOUNT_NOT_FOUND'
            ];
        }

        // Verifica saldo suficiente
        if (!$this->hasSufficientBalance($accountId, $amount)) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Saldo insuficiente. Saldo atual: R$ %.2f, Valor solicitado: R$ %.2f',
                    $account->balance,
                    $amount
                ),
                'error_code' => 'INSUFFICIENT_BALANCE'
            ];
        }

        // Processa o débito
        $success = $this->accountRepository->debitAmount($accountId, $amount);
        
        if (!$success) {
            return [
                'success' => false,
                'message' => 'Erro ao processar o débito na conta.',
                'error_code' => 'DEBIT_FAILED'
            ];
        }

        // Busca o saldo atualizado
        $newBalance = $this->getAccountBalance($accountId);

        return [
            'success' => true,
            'message' => 'Débito processado com sucesso.',
            'data' => [
                'account_id' => $accountId,
                'amount_debited' => $amount,
                'new_balance' => $newBalance,
                'processed_at' => date('Y-m-d H:i:s')
            ]
        ];
    }

    public function createAccount(array $data): Account
    {
        return $this->accountRepository->create($data);
    }

    public function updateAccount(string $accountId, array $data): bool
    {
        return $this->accountRepository->update($accountId, $data);
    }

    public function deleteAccount(string $accountId): bool
    {
        return $this->accountRepository->delete($accountId);
    }

    public function getAllAccounts(): array
    {
        return $this->accountRepository->findAll();
    }

    public function searchAccountsByName(string $name): array
    {
        return $this->accountRepository->findByName($name);
    }
}
