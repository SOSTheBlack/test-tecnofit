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

// Define mock env function for tests if not available
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Create mock Hyperf validation interfaces if not available
if (!interface_exists('Hyperf\\Validation\\Contract\\Rule')) {
    eval('
    namespace Hyperf\\Validation\\Contract {
        interface Rule {
            public function passes(string $attribute, mixed $value): bool;
            public function message(): string;
        }
    }
    ');
}

// Create mock Carbon class if not available
if (!class_exists('Carbon\\Carbon')) {
    eval('
    namespace Carbon {
        class Carbon extends \\DateTime {
            public $timestamp;
            
            public function __construct($time = "now", $timezone = null) {
                parent::__construct($time, $timezone);
                $this->timestamp = $this->getTimestamp();
            }
            
            public static function now($tz = null): static {
                return new static();
            }
            
            public static function parse($time = null, $tz = null): static {
                if ($time === null) {
                    return new static();
                }
                return new static($time);
            }
            
            public static function create($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null): static {
                $date = sprintf("%04d-%02d-%02d %02d:%02d:%02d", 
                    $year ?: date("Y"), 
                    $month ?: 1, 
                    $day ?: 1, 
                    $hour ?: 0, 
                    $minute ?: 0, 
                    $second ?: 0
                );
                return new static($date);
            }
            
            public static function createFromFormat($format, $time, $timezone = null): static|false {
                if ($timezone && is_string($timezone)) {
                    $timezone = new \\DateTimeZone($timezone);
                }
                $result = parent::createFromFormat($format, $time, $timezone);
                if ($result === false) {
                    return false;
                }
                $carbon = new static();
                $carbon->setTimestamp($result->getTimestamp());
                $carbon->timestamp = $carbon->getTimestamp();
                return $carbon;
            }
            
            public static function setTestNow($testNow = null): void {
                // Mock implementation
            }
            
            public function addDays(int $days): static {
                $clone = clone $this;
                $clone->modify("+{$days} days");
                $clone->timestamp = $clone->getTimestamp();
                return $clone;
            }
            
            public function subDays(int $days): static {
                $clone = clone $this;
                $clone->modify("-{$days} days");
                $clone->timestamp = $clone->getTimestamp();
                return $clone;
            }
            
            public function format(string $format): string {
                return parent::format($format);
            }
            
            public function toDateTimeString(): string {
                return $this->format("Y-m-d H:i:s");
            }
            
            public function isPast(): bool {
                return $this->getTimestamp() < time();
            }
            
            public function isFuture(): bool {
                return $this->getTimestamp() > time();
            }
        }
    }
    ');
}

// Create mock Mockery class if not available
if (!class_exists('Mockery')) {
    eval('
    class Mockery {
        public static function mock(string $class = null) {
            return new class {
                private $properties = [];
                
                public function __get($name) {
                    return $this->properties[$name] ?? null;
                }
                
                public function __set($name, $value) {
                    $this->properties[$name] = $value;
                }
                
                public function __call($name, $args) {
                    return $this;
                }
                
                public function shouldReceive($method) {
                    return $this;
                }
                
                public function with(...$args) {
                    return $this;
                }
                
                public function andReturn($value) {
                    return $value;
                }
                
                public function once() {
                    return $this;
                }
                
                public function never() {
                    return $this;
                }
            };
        }
        
        public static function close(): void {
            // Mock implementation
        }
    }
    ');
}

// Initialize Carbon for tests
if (class_exists('Carbon\Carbon')) {
    \Carbon\Carbon::setTestNow();
}

// Explicitly load Mockery if available
if (file_exists(BASE_PATH . '/vendor/mockery/mockery/library/Mockery.php')) {
    require_once BASE_PATH . '/vendor/mockery/mockery/library/Mockery.php';
} elseif (class_exists('\Mockery\Loader\EagerLoader')) {
    $loader = new \Mockery\Loader\EagerLoader();
    $loader->load();
}
