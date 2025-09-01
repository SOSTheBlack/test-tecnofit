<?php

declare(strict_types=1);

namespace App\DataTransfer\Account\Balance;

use App\Enum\PixKeyTypeEnum;
use App\Model\AccountWithdrawPix;

readonly class PixData
{
    public function __construct(
        public PixKeyTypeEnum $type,
        public string $key,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: PixKeyTypeEnum::from($data['type']),
            key: $data['key'],
        );
    }

    public static function fromModel(AccountWithdrawPix $model): self
    {
        return new self(
            type: PixKeyTypeEnum::from($model->type),
            key: $model->key,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'key' => $this->key,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        // Validações específicas por tipo de chave PIX
        switch ($this->type) {
            case PixKeyTypeEnum::EMAIL:
                if (! filter_var($this->key, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email PIX inválido.';
                }

                break;
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
