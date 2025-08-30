<?php

declare(strict_types=1);

namespace App\DTO\Account\Balance;

readonly class AccountSummaryDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public float $currentBalance,
        public float $availableBalance,
        public int $pendingWithdrawsCount,
        public float $pendingWithdrawsAmount,
    ) {}

    public static function fromModel(\App\Model\Account $account): self
    {
        return new self(
            id: $account->id,
            name: $account->name,
            currentBalance: $account->balance,
            availableBalance: $account->getAvailableBalance(),
            pendingWithdrawsCount: $account->pendingWithdraws()->count(),
            pendingWithdrawsAmount: $account->getTotalPendingWithdrawAmount(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'current_balance' => (float) number_format($this->currentBalance, 2, '.', ''),
            'available_balance' => (float) number_format($this->availableBalance, 2, '.', ''),
            'pending_withdraws' => [
                'count' => $this->pendingWithdrawsCount,
                'total_amount' => (float) number_format($this->pendingWithdrawsAmount, 2, '.', ''),
            ],
        ];
    }
}
