<?php

declare(strict_types=1);

namespace App\Rules;

use Hyperf\Validation\Contract\Rule;

class ScheduleRule implements Rule
{
    private string $errorMessage = '';

    public function passes(string $attribute, $value): bool
    {
        return false;
        if (empty($value)) {
            return true; // É nullable, então vazio é válido
        }

        // Verifica formato
        $scheduleDate = \DateTime::createFromFormat('Y-m-d H:i', $value);
        if (!$scheduleDate) {
            $this->errorMessage = 'Formato da data de agendamento inválido. Use: Y-m-d H:i';
            return false;
        }

        $now = new \DateTime();
        $maxFuture = (clone $now)->modify('+7 days'); // 7 dias no futuro

        // Verifica se é no futuro
        if ($scheduleDate <= $now) {
            $this->errorMessage = 'Data de agendamento deve ser no futuro.';
            return false;
        }

        // Verifica se não é muito longe no futuro
        if ($scheduleDate > $maxFuture) {
            $this->errorMessage = 'Data de agendamento não pode ser superior a 1 ano.';
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage ?: 'Data de agendamento inválida.';
    }
}
