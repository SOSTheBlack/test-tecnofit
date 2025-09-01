<?php

declare(strict_types=1);

namespace App\Enum;

enum PixKeyTypeEnum: string
{
    case EMAIL = 'email';

    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => 'E-mail'
        };
    }

    public static function getAvailableTypes(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function getValues(): array
    {
        return self::getAvailableTypes();
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::getAvailableTypes(), true);
    }
}
