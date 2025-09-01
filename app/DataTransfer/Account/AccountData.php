<?php

declare(strict_types=1);

namespace App\DataTransfer\Account;

use App\Model\Account;
use Carbon\Carbon;

readonly class AccountData
{
    public function __construct(
        public string $id,
        public string $name,
        public float $balance,
        public float $availableBalance,
        public float $pendingWithdrawAmount,
        public Carbon $createdAt,
        public ?Carbon $updatedAt = null,
    ) {
    }

    public static function fromModel(Account $account): self
    {
        return new self(
            id: $account->id,
            name: $account->name,
            balance: (float) $account->balance,
            availableBalance: $account->getAvailableBalance(),
            pendingWithdrawAmount: $account->getTotalPendingWithdrawAmount(),
            createdAt: $account->created_at,
            updatedAt: $account->updated_at,
        );
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->availableBalance >= $amount;
    }

    public function canWithdraw(float $amount): bool
    {
        return $this->hasSufficientBalance($amount) && $amount > 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'balance' => $this->balance,
            'available_balance' => $this->availableBalance,
            'pending_withdraw_amount' => $this->pendingWithdrawAmount,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }

    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'balance' => $this->balance,
            'available_balance' => $this->availableBalance,
        ];
    }
}
