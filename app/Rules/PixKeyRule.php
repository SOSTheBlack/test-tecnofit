<?php

declare(strict_types=1);

namespace App\Rules;

use Hyperf\Validation\Contract\Rule;

class PixKeyRule implements Rule
{
    private string $errorMessage = '';

    public function passes(string $attribute, mixed $value): bool
    {
        // Verificar se o valor é uma string
        if (! is_string($value)) {
            $this->errorMessage = 'Chave PIX deve ser uma string.';

            return false;
        }

        // Vamos assumir que a validação do tipo já foi feita pela PixTypeRule
        // Aqui vamos fazer uma validação básica do formato
        if (empty($value)) {
            $this->errorMessage = 'Chave PIX não pode estar vazia.';

            return false;
        }

        if (strlen($value) > 255) {
            $this->errorMessage = 'Chave PIX não pode ter mais de 255 caracteres.';

            return false;
        }

        // Validação básica por formato comum
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return true; // Email válido
        }

        // CPF (11 dígitos)
        if (preg_match('/^\d{11}$/', $value)) {
            return true;
        }

        // CNPJ (14 dígitos)
        if (preg_match('/^\d{14}$/', $value)) {
            return true;
        }

        // Telefone brasileiro
        $cleanPhone = preg_replace('/\D/', '', $value) ?? '';
        if (preg_match('/^(\+55)?(\d{2})(\d{4,5})(\d{4})$/', $cleanPhone)) {
            return true;
        }

        // Chave aleatória (32 caracteres alfanuméricos)
        if (preg_match('/^[a-zA-Z0-9]{32}$/', $value)) {
            return true;
        }

        $this->errorMessage = 'Formato de chave PIX inválido.';

        return false;
    }

    public function message(): string
    {
        return $this->errorMessage ?: 'Chave PIX inválida.';
    }
}
