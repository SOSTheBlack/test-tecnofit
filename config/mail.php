<?php

declare(strict_types=1);

return [
    'driver' => env('MAIL_MAILER', 'smtp'),
    'host' => env('MAIL_HOST', 'mailhog'),
    'port' => env('MAIL_PORT', 1025),
    'username' => env('MAIL_USERNAME'),
    'password' => env('MAIL_PASSWORD'),
    'encryption' => env('MAIL_ENCRYPTION'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@tecnofit.com'),
        'name' => env('MAIL_FROM_NAME', 'Tecnofit PIX API'),
    ],
];
