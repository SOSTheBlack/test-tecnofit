<?php

declare(strict_types=1);

namespace App\Rules;

use Carbon\Carbon;
use Hyperf\Validation\Contract\Rule;
use Throwable;

class ScheduleRule implements Rule
{
    private const DEFAULT_ERROR_MESSAGE = 'Data de agendamento inválida.';
    private const INVALID_FORMAT_MESSAGE = 'Formato da data de agendamento inválido. Use: %s';
    private const PAST_DATE_MESSAGE = 'Data de agendamento deve ser no futuro.';
    private const EXCEEDS_MAX_FUTURE_MESSAGE = 'Data de agendamento não pode ser superior a %d dias.';
    
    private string $errorMessage = self::DEFAULT_ERROR_MESSAGE;
    private const MAX_FUTURE_DAYS = 7;
    private const DATE_FORMAT = 'Y-m-d H:i';

    public function passes(string $attribute, $value): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $scheduleDate = $this->parseDate($value);
        if (!$scheduleDate) {
            return false;
        }

        if (!$this->isValidFutureDate($scheduleDate)) {
            return false;
        }

        if (!$this->isWithinMaxFuture($scheduleDate)) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage;
    }

    /**
     * Verifica se o valor está vazio
     */
    private function isEmpty($value): bool
    {
        return empty($value);
    }

    /**
     * Converte string para Carbon
     */
    private function parseDate($value): ?Carbon
    {
        try {
            return timezone()->createFromFormat(self::DATE_FORMAT, $value);
        } catch (Throwable $e) {
            $this->errorMessage = sprintf(self::INVALID_FORMAT_MESSAGE, self::DATE_FORMAT);
            return null;
        }
    }

    /**
     * Verifica se a data é no futuro
     */
    private function isValidFutureDate(Carbon $scheduleDate): bool
    {
        $now = timezone()->now();
        
        if ($scheduleDate->lte($now)) {
            $this->errorMessage = self::PAST_DATE_MESSAGE;
            return false;
        }

        return true;
    }

    /**
     * Verifica se a data não excede o limite máximo permitido
     */
    private function isWithinMaxFuture(Carbon $scheduleDate): bool
    {
        $now = timezone()->now();
        $maxFuture = $now->copy()->addDays(self::MAX_FUTURE_DAYS);

        if ($scheduleDate->isAfter($maxFuture)) {
            $this->errorMessage = sprintf(self::EXCEEDS_MAX_FUTURE_MESSAGE, self::MAX_FUTURE_DAYS);
            return false;
        }

        return true;
    }
}
