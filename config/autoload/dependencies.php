<?php

declare(strict_types=1);

use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\AccountRepository;

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
];
