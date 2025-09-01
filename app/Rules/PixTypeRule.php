<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enum\PixKeyTypeEnum;
use Hyperf\Validation\Contract\Rule;

class PixTypeRule implements Rule
{
    public function passes(string $attribute, mixed $value): bool
    {
        // Verificar se o valor é uma string
        if (!is_string($value)) {
            return false;
        }

        return PixKeyTypeEnum::isValid($value);
    }

    public function message(): string
    {
        return 'Tipo de chave PIX inválido. Use: ' . implode(', ', PixKeyTypeEnum::getValues());
    }
}
