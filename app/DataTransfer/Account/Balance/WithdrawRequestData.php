<?php

declare(strict_types=1);

namespace App\DataTransfer\Account\Balance;

use App\Enum\WithdrawMethodEnum;
use App\Model\AccountWithdraw;
use Carbon\Carbon;

readonly class WithdrawRequestData
{
    public function __construct(
        public string $accountId,
        public WithdrawMethodEnum $method,
        public float $amount,
        public ?PixData $pix = null,
        public ?Carbon $schedule = null,
        public ?array $metadata = null,
        public ?string $id = null // existe quando é processado pelo cron
    ) {}

    public static function fromArray(array $data): self
    {
        $schedule = null;
        if (isset($data['schedule']) && $data['schedule'] !== null) {
            $schedule = $data['schedule'] instanceof Carbon 
                ? $data['schedule'] 
                : timezone()->parse($data['schedule']);
        }

        $pix = null;
        if (isset($data['pix']) && is_array($data['pix'])) {
            $pix = PixData::fromArray($data['pix']);
        }

        return new self(
            accountId: $data['account_id'],
            method: WithdrawMethodEnum::from($data['method']),
            amount: (float) $data['amount'],
            pix: $pix,
            schedule: $schedule,
            metadata: $data['metadata'] ?? null,
        );
    }

    public static function fromRequest(array $requestData): self
    {
        return self::fromArray([
            'account_id' => $requestData['account_id'],
            'method' => $requestData['method'],
            'amount' => (float) $requestData['amount'],
            'pix' => $requestData['pix'] ?? null,
            'schedule' => $requestData['schedule'] ?? null,
            'metadata' => $requestData['metadata'] ?? null,
        ]);
    }

    public static function fromModel(AccountWithdraw $withdraw): self
    {
        return new self(
            id: $withdraw->id ?? null,
            accountId: $withdraw->account_id,
            method: WithdrawMethodEnum::from($withdraw->method),
            amount: (float) $withdraw->amount,
            pix: $withdraw->pixData ? PixData::fromModel($withdraw->pixData) : null,
            schedule: $withdraw->scheduled_for,
            metadata: $withdraw->meta ?? []
        );
    }

    public function isScheduled(): bool
    {
        if ($this->schedule === null) {
            return false;
        }
        
        // Garantir que usamos o timezone correto
        return $this->schedule->isAfter(timezone()->now());
    }

    public function isImmediate(): bool
    {
        return !$this->isScheduled();
    }

    public function isPixMethod(): bool
    {
        return $this->method === WithdrawMethodEnum::PIX;
    }

    public function hasPixData(): bool
    {
        return $this->pix !== null;
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'method' => $this->method->value,
            'amount' => $this->amount,
            'pix' => $this->pix?->toArray(),
            'schedule' => $this->schedule?->toISOString(),
            'metadata' => $this->metadata,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        // Validação do valor
        if ($this->amount <= 0) {
            $errors[] = 'O valor deve ser maior que zero.';
        }

        // Validação específica para PIX
        if ($this->isPixMethod()) {
            if (!$this->hasPixData()) {
                $errors[] = 'Para saques PIX é necessário informar os dados PIX.';
            } else {
                // Valida os dados PIX se estiverem presentes
                $pixErrors = $this->pix->validate();
                foreach ($pixErrors as $error) {
                    $errors[] = "PIX: {$error}";
                }
            }
        }

        // Validação de agendamento
        if ($this->schedule !== null && is_null($this->id)) {
            $now = timezone()->now();
            if ($this->schedule->isBefore($now)) {
                $errors[] = 'A data de agendamento deve ser futura.';
            }
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }

    // Métodos de conveniência para acessar dados PIX
    public function getPixType(): ?string
    {
        return $this->pix?->type->value;
    }

    public function getPixKey(): ?string
    {
        return $this->pix?->key;
    }

    public function getMaskedPixKey(): ?string
    {
        return $this->pix?->getMaskedKey();
    }
}