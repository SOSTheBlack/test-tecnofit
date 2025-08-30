<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Account\Balance\AccountDataDTO;
use App\DTO\Account\Balance\WithdrawRequestDTO;
use App\DTO\Account\Balance\WithdrawResultDTO;
use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;

class AccountService
{
    private AccountRepositoryInterface $accountRepository;

    public function __construct()
    {
        $this->accountRepository = new \App\Repository\AccountRepository();
    }

    public function findAccountById(string $accountId): ?Account
    {
        return $this->accountRepository->findById($accountId);
    }

    public function getAccountData(string $accountId): ?AccountDataDTO
    {
        $account = $this->findAccountById($accountId);
        
        return $account ? AccountDataDTO::fromModel($account) : null;
    }
}
