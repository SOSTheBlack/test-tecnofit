<?php

declare(strict_types=1);

namespace App\Helper;

use Carbon\Carbon;

class TimezoneHelper
{
    private string $appTimezone;

    public function __construct()
    {
        // Obtém timezone da configuração ou usa padrão
        $this->appTimezone = env('APP_TIMEZONE', 'America/Sao_Paulo');
        
        // Configura o timezone padrão do Carbon
        Carbon::setTestNow(); // Reset test time
        
        // Configura o timezone do PHP
        date_default_timezone_set($this->appTimezone);
    }

    public function now(): Carbon
    {
        return Carbon::now($this->appTimezone);
    }

    public function parse(string $datetime): Carbon
    {
        return Carbon::parse($datetime, $this->appTimezone);
    }

    public function createFromFormat(string $format, string $datetime): Carbon
    {
        return Carbon::createFromFormat($format, $datetime, $this->appTimezone);
    }

    public function getTimezone(): string
    {
        return $this->appTimezone;
    }

    public function isInPast(Carbon $date): bool
    {
        return $date->isBefore($this->now());
    }

    public function isInFuture(Carbon $date): bool
    {
        return $date->isAfter($this->now());
    }
}
