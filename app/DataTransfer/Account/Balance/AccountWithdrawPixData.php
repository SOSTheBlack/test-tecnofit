<?php

declare(strict_types=1);

namespace App\DataTransfer\Account\Balance;

use App\Enum\PixKeyTypeEnum;
use App\Model\AccountWithdrawPix;
use Carbon\Carbon;

/**
 * DTO para dados PIX de saques
 * 
 * Representa os dados específicos do PIX vinculados a um saque,
 * fornecendo uma camada de abstração sobre o modelo AccountWithdrawPix
 */
readonly class AccountWithdrawPixData
{
    public function __construct(
        public string $id,
        public string $accountWithdrawId,
        public ?string $externalId,
        public PixKeyTypeEnum $type,
        public string $key,
        public Carbon $createdAt,
        public ?Carbon $updatedAt = null,
    ) {}

    /**
     * Cria instância a partir do modelo
     * 
     * @param AccountWithdrawPix $model
     * @return self
     */
    public static function fromModel(AccountWithdrawPix $model): self
    {
        return new self(
            id: $model->id,
            accountWithdrawId: $model->account_withdraw_id,
            externalId: $model->external_id,
            type: PixKeyTypeEnum::from($model->type),
            key: $model->key,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    /**
     * Cria instância a partir de array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            accountWithdrawId: $data['account_withdraw_id'],
            externalId: $data['external_id'] ?? null,
            type: PixKeyTypeEnum::from($data['type']),
            key: $data['key'],
            createdAt: timezone()->parse($data['created_at']),
            updatedAt: isset($data['updated_at']) ? timezone()->parse($data['updated_at']) : null,
        );
    }

    /**
     * Converte para array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'account_withdraw_id' => $this->accountWithdrawId,
            'external_id' => $this->externalId,
            'type' => $this->type->value,
            'key' => $this->key,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }

    /**
     * Converte para PixData simples
     * 
     * @return PixData
     */
    public function toPixData(): PixData
    {
        return new PixData(
            type: $this->type,
            key: $this->key
        );
    }

    /**
     * Valida os dados PIX
     * 
     * @return array Lista de erros de validação
     */
    public function validate(): array
    {
        $errors = [];

        // Validação da chave baseada no tipo
        switch ($this->type) {
            case PixKeyTypeEnum::EMAIL:
                if (!filter_var($this->key, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email PIX inválido.';
                }
                break;

            case PixKeyTypeEnum::PHONE:
                // Validação simplificada para telefone brasileiro
                if (!preg_match('/^\+55\s?\d{2}\s?\d{4,5}-?\d{4}$/', $this->key)) {
                    $errors[] = 'Telefone PIX inválido. Formato esperado: +55 (XX) 9XXXX-XXXX';
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

            case PixKeyTypeEnum::RANDOM_KEY:
                if (strlen($this->key) !== 32 || !ctype_alnum($this->key)) {
                    $errors[] = 'Chave PIX aleatória deve ter 32 caracteres alfanuméricos.';
                }
                break;
        }

        return $errors;
    }

    /**
     * Verifica se os dados são válidos
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Obtém rótulo legível do tipo
     * 
     * @return string
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            PixKeyTypeEnum::EMAIL => 'Email',
            PixKeyTypeEnum::PHONE => 'Telefone',
            PixKeyTypeEnum::CPF => 'CPF',
            PixKeyTypeEnum::CNPJ => 'CNPJ',
            PixKeyTypeEnum::RANDOM_KEY => 'Chave Aleatória',
        };
    }

    /**
     * Formata a chave para exibição
     * 
     * @return string
     */
    public function getFormattedKey(): string
    {
        return match ($this->type) {
            PixKeyTypeEnum::EMAIL => $this->key,
            PixKeyTypeEnum::PHONE => $this->formatPhone($this->key),
            PixKeyTypeEnum::CPF => $this->formatCpf($this->key),
            PixKeyTypeEnum::CNPJ => $this->formatCnpj($this->key),
            PixKeyTypeEnum::RANDOM_KEY => substr($this->key, 0, 8) . '...' . substr($this->key, -8),
        };
    }

    /**
     * Valida CPF
     * 
     * @param string $cpf
     * @return bool
     */
    private function isValidCpf(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verifica sequências inválidas
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Valida CNPJ
     * 
     * @param string $cnpj
     * @return bool
     */
    private function isValidCnpj(string $cnpj): bool
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verifica se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        // Verifica sequências inválidas
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Validação dos dígitos verificadores
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        // Primeiro dígito
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if ($cnpj[12] != $digit1) {
            return false;
        }
        
        // Segundo dígito
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        return $cnpj[13] == $digit2;
    }

    /**
     * Formata telefone para exibição
     * 
     * @param string $phone
     * @return string
     */
    private function formatPhone(string $phone): string
    {
        $numbers = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numbers) === 13) { // +55 + DDD + número
            return '+55 (' . substr($numbers, 2, 2) . ') ' . substr($numbers, 4, 5) . '-' . substr($numbers, 9, 4);
        }
        return $phone;
    }

    /**
     * Formata CPF para exibição
     * 
     * @param string $cpf
     * @return string
     */
    private function formatCpf(string $cpf): string
    {
        $numbers = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($numbers) === 11) {
            return substr($numbers, 0, 3) . '.' . substr($numbers, 3, 3) . '.' . 
                   substr($numbers, 6, 3) . '-' . substr($numbers, 9, 2);
        }
        return $cpf;
    }

    /**
     * Formata CNPJ para exibição
     * 
     * @param string $cnpj
     * @return string
     */
    private function formatCnpj(string $cnpj): string
    {
        $numbers = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($numbers) === 14) {
            return substr($numbers, 0, 2) . '.' . substr($numbers, 2, 3) . '.' . 
                   substr($numbers, 5, 3) . '/' . substr($numbers, 8, 4) . '-' . substr($numbers, 12, 2);
        }
        return $cnpj;
    }
}