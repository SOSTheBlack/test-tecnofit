<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Model\Account;

interface AccountRepositoryInterface
{
    /**
     * @param string $accountId
     * 
     * @return Account|null
     * 
     * @throws ModelNotFoundException
     */
    public function findById(string $accountId): ?Account;
    
    public function getBalance(string $accountId): ?float;
    
    public function hasSufficientBalance(string $accountId, float $amount): bool;
    
    public function updateBalance(string $accountId, float $newBalance): bool;
    
    public function debitAmount(string $accountId, float $amount): bool;
    
    public function create(array $data): Account;
    
    public function update(string $accountId, array $data): bool;
    
    public function delete(string $accountId): bool;
    
    public function findAll(): array;
    
    public function findByName(string $name): array;
}
