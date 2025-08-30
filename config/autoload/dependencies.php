<?php

declare(strict_types=1);

use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;

return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
    ],
    
    // Repository bindings
    AccountRepositoryInterface::class => AccountRepository::class,
    AccountWithdrawRepositoryInterface::class => AccountWithdrawRepository::class,
];
