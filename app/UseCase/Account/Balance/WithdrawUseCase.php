<?php

declare(strict_types=1);

namespace App\UseCase\Account\Balance;

use App\Model\Account;
use App\Service\AccountService;
use Carbon\Carbon;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\DTO\Account\Balance\WithdrawRequestDTO;
use App\DTO\Account\Balance\WithdrawResultDTO;
use App\Enum\WithdrawMethodEnum;
use App\Rules\PixKeyRule;
use App\Rules\PixTypeRule;
use App\Rules\ScheduleRule;
use App\Rules\WithdrawMethodRule;

class WithdrawUseCase
{
    private Account $account;
    private WithdrawRequestDTO $withdrawRequestDTO;


    public function __construct(private AccountService $accountService)
    {

    }

    /**
     * Executa o saque (imediato ou agendado)
     */
    public function execute(WithdrawRequestDTO $withdrawRequestDTO): WithdrawResultDTO
    {
        $this->prepareExecute($withdrawRequestDTO);

        // Validação inicial do DTO
        if (!$this->isValid()) {
            return WithdrawResultDTO::validationError($withdrawRequestDTO->validate());
        }

        // Validar se tem saldo suficiente
        if (!$this->hasSufficientBalance()) {
            return WithdrawResultDTO::error(
                'INSUFFICIENT_BALANCE',
                sprintf(
                    'Saldo insuficiente. Saldo disponível: R$ %.2f, Valor solicitado: R$ %.2f',
                    $this->account->availableBalance,
                    $this->withdrawRequestDTO->amount
                )
            );
            // throw new InvalidArgumentException('Saldo insuficiente para realizar o saque.');
        }

        // Se for agendamento
        if ($this->withdrawRequestDTO->schedule !== null) {
            return $this->accountService->processImmediateWithdraw();
        }

        // Saque imediato
        return $this->processImmediateWithdraw();
    }

    private function prepareExecute(WithdrawRequestDTO $withdrawRequestDTO): void
    {
        $this->withdrawRequestDTO = $withdrawRequestDTO;
        $this->account = $this->accountService->findAccountById($withdrawRequestDTO->accountId);
    }

    private function isValid(): bool
    {
        $errors = [];

        // Validação do valor
        if ($this->withdrawRequestDTO->amount <= 0) {
            $errors[] = 'O valor deve ser maior que zero.';
        }

        return count($errors) === 0;
    }

    /**
     * Processa saque imediato
     */
    private function processImmediateWithdraw(): array
    {
        try {
            // Processa o débito usando o Service
            $withdrawResult = $this->accountService->processWithdraw($this->withdrawRequestDTO);

            if (!$withdrawResult['success']) {
                return $this->buildErrorResponse($withdrawResult['message']);
            }

            return $this->buildSuccessResponse(
                'Saque processado com sucesso.',
                [
                    'account_id' => $this->account->id,
                    'amount' => $this->withdrawRequestDTO->amount,
                    'method' => $this->withdrawRequestDTO->method,
                    'pix' => $this->withdrawRequestDTO->pix,
                    'new_balance' => $withdrawResult['data']['new_balance'],
                    'processed_at' => $withdrawResult['data']['processed_at'],
                    'transaction_id' => $this->generateTransactionId(),
                    'type' => 'immediate'
                ]
            );

        } catch (\Exception $e) {
            return $this->buildErrorResponse('Erro ao processar saque: ' . $e->getMessage());
        }
    }

    /**
     * Agenda o saque para data futura
     */
    private function scheduleWithdraw(): array
    {
        try {
            // Aqui você implementaria a lógica de agendamento
            // Criar registro na tabela de saques agendados
            // Configurar job para processar na data agendada
            
            // Por enquanto, simulando o agendamento
            $scheduledData = [
                'account_id' => $this->account->id,
                'amount' => $this->withdrawRequestDTO->amount,
                'method' => $this->withdrawRequestDTO->method,
                'pix' => $this->withdrawRequestDTO->pix,
                'scheduled_for' => $this->withdrawRequestDTO->schedule,
                'current_balance' => (float) $this->account->balance,
                'scheduled_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'transaction_id' => $this->generateTransactionId(),
                'type' => 'scheduled',
                'status' => 'pending'
            ];

            return $this->buildSuccessResponse(
                'Saque agendado com sucesso.',
                $scheduledData,
                201
            );

        } catch (\Exception $e) {
            return $this->buildErrorResponse('Erro ao agendar saque: ' . $e->getMessage());
        }
    }

    /**
     * Verifica se a conta tem saldo suficiente
     */
    private function hasSufficientBalance(): bool
    {
        return (float) $this->account->balance >= $this->withdrawRequestDTO->amount;
    }

    /**
     * Constrói resposta de sucesso padronizada
     */
    private function buildSuccessResponse(string $message, array $data, int $statusCode = 200): array
    {
        return [
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Constrói resposta de erro padronizada
     */
    private function buildErrorResponse(string $message, int $statusCode = 400): array
    {
        return [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => null
        ];
    }

    /**
     * Gera ID único para a transação
     */
    private function generateTransactionId(): string
    {
        return 'TXN_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Valida os dados de entrada
     */
    public function validateWithdrawData(array $withdrawData): array
    {
        $errors = [];

        // Validar amount
        if (!isset($withdrawData['amount']) || $withdrawData['amount'] <= 0) {
            $errors[] = 'Valor do saque deve ser maior que zero.';
        }

        // Validar method
        if (!isset($withdrawData['method']) || empty($withdrawData['method'])) {
            $errors[] = 'Método de saque é obrigatório.';
        }

        // Validar dados PIX
        if (!isset($withdrawData['pix']) || !is_array($withdrawData['pix'])) {
            $errors[] = 'Dados do PIX são obrigatórios.';
        } else {
            if (!isset($withdrawData['pix']['type']) || empty($withdrawData['pix']['type'])) {
                $errors[] = 'Tipo da chave PIX é obrigatório.';
            }
            if (!isset($withdrawData['pix']['key']) || empty($withdrawData['pix']['key'])) {
                $errors[] = 'Chave PIX é obrigatória.';
            }
        }

        // Validar agendamento se fornecido
        if (isset($withdrawData['schedule']) && !empty($withdrawData['schedule'])) {
            try {
                $scheduleDate = Carbon::createFromFormat('Y-m-d H:i', $withdrawData['schedule']);
                if ($scheduleDate->isPast()) {
                    $errors[] = 'Data de agendamento deve ser no futuro.';
                }
            } catch (\Exception $e) {
                $errors[] = 'Formato da data de agendamento inválido. Use: Y-m-d H:i';
            }
        }

        return $errors;
    }

    /**
     * Calcula taxa de saque (se aplicável)
     */
    private function calculateWithdrawFee(float $amount, string $method): float
    {
        // Implementar lógica de cálculo de taxa
        // Por exemplo: PIX pode ter taxa diferente de TED
        switch ($method) {
            case 'PIX':
                return $amount * 0.001; // 0.1% de taxa
            case 'TED':
                return 5.00; // Taxa fixa
            default:
                return 0.00;
        }
    }

    /**
     * Aplica taxa no valor do saque
     */
    private function applyWithdrawFee(float $amount, string $method): array
    {
        $fee = $this->calculateWithdrawFee($amount, $method);
        $totalAmount = $amount + $fee;

        return [
            'gross_amount' => $amount,
            'fee' => $fee,
            'net_amount' => $totalAmount
        ];
    }
}
