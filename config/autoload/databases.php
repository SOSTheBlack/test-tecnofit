<?php

declare(strict_types=1);

// Configuração dinâmica baseada no ambiente
$isTestingEnv = env('APP_ENV') === 'testing';
$defaultTestHost = $isTestingEnv ? '127.0.0.1' : 'mysql';

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'hyperf'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ],
        'commands' => [
            'gen:model' => [
                'path' => 'app/Model',
                'force_casts' => true,
                'inheritance' => 'Model',
            ],
        ],
    ],
    'test' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', $defaultTestHost),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'tecnofit_pix_test'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', 'root'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ],
        'commands' => [
            'gen:model' => [
                'path' => 'app/Model',
                'force_casts' => true,
                'inheritance' => 'Model',
            ],
        ],
    ],
];
