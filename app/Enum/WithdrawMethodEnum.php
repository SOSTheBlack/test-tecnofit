<?php

declare(strict_types=1);

namespace App\Enum;

enum WithdrawMethodEnum: string
{
    case PIX = 'PIX';

    public function getLabel(): string
    {
        return match ($this) {
            self::PIX => 'PIX',
        };
    }

    public static function getAvailableMethods(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function getValues(): array
    {
        return self::getAvailableMethods();
    }

    public static function isValid(string $method): bool
    {
        return in_array($method, self::getAvailableMethods(), true);
    }
}
