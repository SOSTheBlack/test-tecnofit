<?php

declare(strict_types=1);

namespace App\Service;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\WithdrawRequestData;

/**
 * Classe responsável pelas regras de negócio puras relacionadas a saques
 * 
 * Esta classe contém apenas regras de negócio sem dependências externas,
 * seguindo os princípios da Clean Architecture
 */
class WithdrawBusinessRules
{
    /**
     * Valida se o saque pode ser realizado baseado nas regras de negócio
     * 
     * @param AccountData $accountData Dados da conta
     * @param WithdrawRequestData $withdrawRequestData Dados da solicitação de saque
     * @return array Lista de erros de validação (vazia se válido)
     */
    public function validateWithdrawRequest(AccountData $accountData, WithdrawRequestData $withdrawRequestData): array
    {
        $errors = [];

        // Regra: Validar saldo disponível
        if (!$this->hasSufficientBalance($accountData, $withdrawRequestData->amount)) {
            $errors[] = sprintf(
                'Saldo insuficiente. Saldo disponível: R$ %.2f, Valor solicitado: R$ %.2f',
                $accountData->availableBalance,
                $withdrawRequestData->amount
            );
        }

        // Regra: Validar limite mínimo de saque
        if (!$this->isAmountAboveMinimum($withdrawRequestData->amount)) {
            $errors[] = 'Valor mínimo para saque é R$ 0,01';
        }

        // Regra: Validar se a conta está ativa
        if (!$this->isAccountActive($accountData)) {
            $errors[] = 'Conta inativa. Não é possível realizar saques';
        }

        return $errors;
    }

    /**
     * Verifica se há saldo suficiente para o saque
     */
    public function hasSufficientBalance(AccountData $accountData, float $amount): bool
    {
        return $accountData->canWithdraw($amount);
    }

    /**
     * Verifica se o valor está acima do mínimo permitido
     */
    public function isAmountAboveMinimum(float $amount): bool
    {
        return $amount >= 0.01;
    }

    /**
     * Verifica se a conta está ativa
     * 
     * @param AccountData $accountData
     * @return bool
     */
    public function isAccountActive(AccountData $accountData): bool
    {
        // Por enquanto, considera todas as contas como ativas
        // Esta regra pode ser expandida conforme necessário
        return true;
    }

    /**
     * Calcula o novo saldo após o saque
     */
    public function calculateNewBalanceAfterWithdraw(AccountData $accountData, float $amount): array
    {
        return [
            'current_balance' => (float) number_format($accountData->balance - $amount, 2, '.', ''),
            'available_balance' => (float) number_format($accountData->availableBalance - $amount, 2, '.', ''),
        ];
    }

    /**
     * Determina se deve enviar notificação por email
     */
    public function shouldSendEmailNotification(WithdrawRequestData $withdrawRequestData): bool
    {
        return $withdrawRequestData->isPixMethod() && $withdrawRequestData->getPixType() === 'email';
    }
}