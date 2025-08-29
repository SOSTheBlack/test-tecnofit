<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enum\PixKeyTypeEnum;
use Hyperf\Validation\Contract\Rule;

class PixTypeRule implements Rule
{
    public function passes(string $attribute, $value): bool
    {
        return PixKeyTypeEnum::isValid($value);
    }

    public function message(): string
    {
        return 'Tipo de chave PIX inválido. Use: ' . implode(', ', PixKeyTypeEnum::getValues());
    }
}
