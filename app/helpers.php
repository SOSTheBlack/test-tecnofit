<?php

declare(strict_types=1);

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ContainerInterface;

if (! function_exists('container')) {
    function container(): ContainerInterface
    {
        return ApplicationContext::getContainer();
    }
}

if (! function_exists('redis')) {
    function redis(): \Hyperf\Redis\Redis
    {
        return container()->get(\Hyperf\Redis\Redis::class);
    }
}

if (! function_exists('logger')) {
    function logger(string $name = 'app'): \Psr\Log\LoggerInterface
    {
        return container()->get(\Hyperf\Logger\LoggerFactory::class)->get($name);
    }
}

if (! function_exists('config')) {
    function config(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return container()->get(\Hyperf\Contract\ConfigInterface::class);
        }

        return container()->get(\Hyperf\Contract\ConfigInterface::class)->get($key, $default);
    }
}

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (! function_exists('timezone')) {
    function timezone(): \App\Helper\TimezoneHelper
    {
        return container()->get(\App\Helper\TimezoneHelper::class);
    }
}
