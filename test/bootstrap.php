<?php

declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', extension_loaded('swoole') ? (SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL) : 0);

require BASE_PATH . '/vendor/autoload.php';

// Set test environment
putenv('APP_ENV=testing');
putenv('DB_DATABASE=tecnofit_pix_test');

// Try to initialize Hyperf ClassLoader if available
if (class_exists('Hyperf\Di\ClassLoader')) {
    Hyperf\Di\ClassLoader::init();
}
