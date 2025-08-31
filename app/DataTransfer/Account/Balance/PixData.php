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
    ) {}

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
                if (!filter_var($this->key, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email PIX inválido.';
                }
                break;
            
            case PixKeyTypeEnum::CPF:
                if (!$this->isValidCpf($this->key)) {
                    $errors[] = 'CPF PIX inválido.';
                }
                break;
            
            case PixKeyTypeEnum::CNPJ:
                if (!$this->isValidCnpj($this->key)) {
                    $errors[] = 'CNPJ PIX inválido.';
                }
                break;
            
            case PixKeyTypeEnum::PHONE:
                if (!$this->isValidPhone($this->key)) {
                    $errors[] = 'Telefone PIX inválido.';
                }
                break;
            
            case PixKeyTypeEnum::RANDOM_KEY:
                if (!$this->isValidRandomKey($this->key)) {
                    $errors[] = 'Chave PIX aleatória inválida.';
                }
                break;
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

        if ($result != $digits[0]) {
            return false;
        }

        $length++;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

        return $result == $digits[1];
    }

    private function isValidPhone(string $phone): bool
    {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        return strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 11;
    }

    private function isValidRandomKey(string $key): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key) === 1;
    }

    public function getMaskedKey(): string
    {
        switch ($this->type) {
            case PixKeyTypeEnum::CPF:
            case PixKeyTypeEnum::CNPJ:
                return substr($this->key, 0, 3) . str_repeat('*', strlen($this->key) - 6) . substr($this->key, -3);
            
            case PixKeyTypeEnum::EMAIL:
                $parts = explode('@', $this->key);
                if (count($parts) === 2) {
                    $name = substr($parts[0], 0, 2) . str_repeat('*', max(0, strlen($parts[0]) - 2));
                    return $name . '@' . $parts[1];
                }
                return $this->key;
            
            case PixKeyTypeEnum::PHONE:
                $cleanPhone = preg_replace('/\D/', '', $this->key);
                return substr($cleanPhone, 0, 2) . str_repeat('*', max(0, strlen($cleanPhone) - 4)) . substr($cleanPhone, -2);
            
            case PixKeyTypeEnum::RANDOM_KEY:
            default:
                return substr($this->key, 0, 8) . str_repeat('*', max(0, strlen($this->key) - 16)) . substr($this->key, -8);
        }
    }
}
