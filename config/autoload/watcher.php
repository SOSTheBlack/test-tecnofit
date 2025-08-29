<?php

declare(strict_types=1);

use Hyperf\Watcher\Driver\ScanFileDriver;

return [
    'driver' => ScanFileDriver::class,
    'bin' => 'php',
    'watch' => [
        'dir' => ['app', 'config'],
        'file' => ['.env'],
        'scan_interval' => 2000,
    ],
    'ignore' => [
        'vendor',
        'runtime',
        'storage',
        'test',
        'tests',
        '.git',
        '.github',
        '.gitlab',
        'node_modules',
        '*.log',
        '*.md',
        '*.txt',
        '*.lock',
        '*.cache',
    ],
    'restart' => [
        'enable' => true,
        'max_count' => 3,
        'interval' => 2000,
    ],
];
