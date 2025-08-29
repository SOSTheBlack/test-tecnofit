<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enum\WithdrawMethodEnum;
use Hyperf\Validation\Contract\Rule;

class WithdrawMethodRule implements Rule
{
    public function passes(string $attribute, $value): bool
    {
        return WithdrawMethodEnum::isValid($value);
    }

    public function message(): string
    {
        return 'Método de saque inválido. Use: ' . implode(', ', WithdrawMethodEnum::getValues());
    }
    
}
