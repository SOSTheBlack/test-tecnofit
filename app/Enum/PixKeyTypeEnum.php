<?php

declare(strict_types=1);

namespace App\Enum;

enum PixKeyTypeEnum: string
{
    case EMAIL = 'email';
    // case PHONE = 'phone';
    // case CPF = 'CPF';
    // case CNPJ = 'CNPJ';
    // case RANDOM_KEY = 'random_key';

    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => 'E-mail',
            // self::PHONE => 'Telefone',
            // self::CPF => 'CPF',
            // self::CNPJ => 'CNPJ',
            // self::RANDOM_KEY => 'Chave Aleatória',
        };
    }

    public static function getAvailableTypes(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
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
