<?php

declare(strict_types=1);

namespace App\DataTransfer\Account\Balance;

use App\Enum\WithdrawMethodEnum;
use App\Model\AccountWithdraw;
use Carbon\Carbon;

/**
 * DTO (Data Transfer Object) para dados de saque da conta
 * 
 * Representa um saque de conta bancária com todos os seus atributos e metadados.
 * Esta classe implementa o padrão DTO (Data Transfer Object) e segue os princípios
 * de immutability através do uso da palavra-chave readonly.
 * 
 * A classe fornece métodos utilitários para verificação de status, formatação
 * de dados para exibição e conversão entre diferentes formatos de representação.
 * 
 * @package App\DataTransfer\Account\Balance
 * @version 1.0.0
 * @since 1.0.0
 * 
 * @example
 * ```php
 * $withdrawData = AccountWithdrawData::fromModel($accountWithdrawModel);
 * 
 * if ($withdrawData->isCompleted()) {
 *     echo "Saque concluído: " . $withdrawData->getFormattedAmount();
 * }
 * 
 * // Converte para array para API response
 * $apiResponse = $withdrawData->toDetailedResponse();
 * ```
 * 
 * @see WithdrawRequestData Para dados de requisição de saque
 * @see WithdrawResultData Para resultado de operações de saque
 * @see AccountData Para dados da conta associada
 * 
 * @author Sistema Tecnofit PIX API
 */
readonly class AccountWithdrawData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $transactionId,
        public WithdrawMethodEnum $method,
        public float $amount,
        public bool $scheduled,
        public string $status,
        public bool $error,
        public ?string $errorReason,
        public ?array $meta,
        public ?Carbon $scheduledFor,
        public Carbon $createdAt,
        public bool $done = false,
        public ?Carbon $updatedAt = null,
    ) {}

    public static function fromModel(AccountWithdraw $model): self
    {
        return new self(
            id: (string) $model->id,
            accountId: (string) $model->account_id,
            transactionId: (string) $model->transaction_id,
            method: WithdrawMethodEnum::from($model->method),
            amount: (float) $model->amount, // Cast para float devido ao decimal cast do modelo
            scheduled: $model->scheduled,
            status: $model->status,
            done: $model->done ?? false,
            error: $model->error ?? false,
            errorReason: $model->error_reason,
            meta: $model->meta,
            scheduledFor: $model->scheduled_for,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public static function fromArray(array $data): self
    {
        $scheduledFor = null;
        if (isset($data['scheduled_for']) && $data['scheduled_for'] !== null) {
            $scheduledFor = $data['scheduled_for'] instanceof Carbon 
                ? $data['scheduled_for'] 
                : timezone()->parse($data['scheduled_for']);
        }

        return new self(
            id: $data['id'],
            accountId: $data['account_id'],
            transactionId: $data['transaction_id'],
            method: WithdrawMethodEnum::from($data['method']),
            amount: (float) $data['amount'],
            scheduled: (bool) $data['scheduled'],
            status: $data['status'],
            done: (bool) $data['done'],
            error: (bool) $data['error'],
            errorReason: $data['error_reason'] ?? null,
            meta: $data['meta'] ?? null,
            scheduledFor: $scheduledFor,
            createdAt: timezone()->parse($data['created_at']),
            updatedAt: isset($data['updated_at']) ? timezone()->parse($data['updated_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'transaction_id' => $this->transactionId,
            'method' => $this->method->value,
            'amount' => $this->amount,
            'scheduled' => $this->scheduled,
            'status' => $this->status,
            'done' => $this->done,
            'error' => $this->error,
            'error_reason' => $this->errorReason,
            'meta' => $this->meta,
            'scheduled_for' => $this->scheduledFor?->toISOString(),
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }

    // Status verification methods
    public function isPending(): bool
    {
        return $this->status === AccountWithdraw::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === AccountWithdraw::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === AccountWithdraw::STATUS_COMPLETED && $this->done;
    }

    public function isFailed(): bool
    {
        return $this->status === AccountWithdraw::STATUS_FAILED || $this->error;
    }

    public function isScheduled(): bool
    {
        return $this->scheduled && $this->scheduledFor !== null;
    }

    public function isReadyForExecution(): bool
    {
        return $this->isScheduled() 
            && $this->scheduledFor <= timezone()->now()
            && $this->isPending();
    }

    public function isPixMethod(): bool
    {
        return $this->method === WithdrawMethodEnum::PIX;
    }

    // Convenience methods for display
    public function getFormattedAmount(): string
    {
        return 'R$ ' . number_format($this->amount, 2, ',', '.');
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            AccountWithdraw::STATUS_PENDING => 'Pendente',
            AccountWithdraw::STATUS_PROCESSING => 'Processando',
            AccountWithdraw::STATUS_COMPLETED => 'Concluído',
            AccountWithdraw::STATUS_FAILED => 'Falhou',
            AccountWithdraw::STATUS_CANCELLED => 'Cancelado',
            AccountWithdraw::STATUS_SCHEDULED => 'Agendado',
            default => 'Desconhecido',
        };
    }

    public function getMethodLabel(): string
    {
        return $this->method->getLabel();
    }

    public function getTypeLabel(): string
    {
        return $this->scheduled ? 'Agendado' : 'Imediato';
    }

    // Summary methods for API responses
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transactionId,
            'method' => $this->method->value,
            'amount' => $this->amount,
            'status' => $this->status,
            'scheduled' => $this->scheduled,
            'scheduled_for' => $this->scheduledFor?->toISOString(),
            'created_at' => $this->createdAt->toISOString(),
        ];
    }

    public function toDetailedResponse(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'transaction_id' => $this->transactionId,
            'method' => [
                'value' => $this->method->value,
                'label' => $this->getMethodLabel(),
            ],
            'amount' => [
                'value' => $this->amount,
                'formatted' => $this->getFormattedAmount(),
            ],
            'status' => [
                'value' => $this->status,
                'label' => $this->getStatusLabel(),
            ],
            'type' => [
                'scheduled' => $this->scheduled,
                'label' => $this->getTypeLabel(),
            ],
            'scheduled_for' => $this->scheduledFor?->toISOString(),
            'completed' => $this->done,
            'error' => $this->error,
            'error_reason' => $this->errorReason,
            'metadata' => $this->meta,
            'timestamps' => [
                'created_at' => $this->createdAt->toISOString(),
                'updated_at' => $this->updatedAt?->toISOString(),
            ],
        ];
    }

    // Validation methods
    public function canBeProcessed(): bool
    {
        return $this->isPending() && !$this->error;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            AccountWithdraw::STATUS_PENDING,
            AccountWithdraw::STATUS_SCHEDULED,
        ]) && !$this->done;
    }

    public function canBeRetried(): bool
    {
        return $this->isFailed() && !$this->done;
    }

    // Time-related methods
    public function getProcessingTime(): ?int
    {
        if ($this->updatedAt !== null && $this->createdAt !== null) {
            return $this->createdAt->diffInSeconds($this->updatedAt);
        }
        return null;
    }

    public function isExpired(int $hoursToExpire = 24): bool
    {
        if ($this->isScheduled() && $this->scheduledFor) {
            return timezone()->now()->isAfter($this->scheduledFor->addHours($hoursToExpire));
        }
        
        return timezone()->now()->isAfter($this->createdAt->addHours($hoursToExpire));
    }

    public function getDaysUntilScheduled(): ?int
    {
        if (!$this->isScheduled()) {
            return null;
        }

        return timezone()->now()->diffInDays($this->scheduledFor, false);
    }
}
