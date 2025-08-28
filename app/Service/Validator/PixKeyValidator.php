<?php

declare(strict_types=1);

namespace App\Service\Validator;

use App\Enum\PixKeyTypeEnum;

class PixKeyValidator
{
    public static function validateKey(string $type, string $key): array
    {
        return match ($type) {
            PixKeyTypeEnum::EMAIL->value => self::validateEmail($key),
            PixKeyTypeEnum::PHONE->value => self::validatePhone($key),
            PixKeyTypeEnum::CPF->value => self::validateCpf($key),
            PixKeyTypeEnum::CNPJ->value => self::validateCnpj($key),
            PixKeyTypeEnum::RANDOM_KEY->value => self::validateRandomKey($key),
            default => ['valid' => false, 'message' => 'Tipo de chave PIX inválido.']
        };
    }

    private static function validateEmail(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Formato de e-mail inválido.'];
        }

        if (strlen($email) > 77) {
            return ['valid' => false, 'message' => 'E-mail não pode ter mais de 77 caracteres.'];
        }

        return ['valid' => true, 'message' => 'E-mail válido.'];
    }

    private static function validatePhone(string $phone): array
    {
        // Remove todos os caracteres não numéricos
        $cleanPhone = preg_replace('/\D/', '', $phone);

        // Valida formato brasileiro: (11) 99999-9999 ou +5511999999999
        if (!preg_match('/^(\+55)?(\d{2})(\d{4,5})(\d{4})$/', $cleanPhone)) {
            return ['valid' => false, 'message' => 'Formato de telefone inválido. Use o formato (11) 99999-9999.'];
        }

        // Verifica se tem 10 ou 11 dígitos (sem código do país)
        $phoneLength = strlen($cleanPhone);
        if ($phoneLength === 13) { // +55 + 11 dígitos
            $cleanPhone = substr($cleanPhone, 2); // Remove +55
        }

        if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 11) {
            return ['valid' => false, 'message' => 'Telefone deve ter 10 ou 11 dígitos.'];
        }

        return ['valid' => true, 'message' => 'Telefone válido.'];
    }

    private static function validateCpf(string $cpf): array
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/\D/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($cpf) !== 11) {
            return ['valid' => false, 'message' => 'CPF deve ter 11 dígitos.'];
        }

        // Verifica se não são todos os dígitos iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return ['valid' => false, 'message' => 'CPF inválido.'];
        }

        // Validação dos dígitos verificadores
        if (!self::validateCpfDigits($cpf)) {
            return ['valid' => false, 'message' => 'CPF inválido.'];
        }

        return ['valid' => true, 'message' => 'CPF válido.'];
    }

    private static function validateCpfDigits(string $cpf): bool
    {
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += (int)$cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int)$cpf[$c] !== $d) {
                return false;
            }
        }
        return true;
    }

    private static function validateCnpj(string $cnpj): array
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/\D/', '', $cnpj);

        // Verifica se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return ['valid' => false, 'message' => 'CNPJ deve ter 14 dígitos.'];
        }

        // Verifica se não são todos os dígitos iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return ['valid' => false, 'message' => 'CNPJ inválido.'];
        }

        // Validação dos dígitos verificadores
        if (!self::validateCnpjDigits($cnpj)) {
            return ['valid' => false, 'message' => 'CNPJ inválido.'];
        }

        return ['valid' => true, 'message' => 'CNPJ válido.'];
    }

    private static function validateCnpjDigits(string $cnpj): bool
    {
        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += (int)$numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        if ((int)$digits[0] !== $result) {
            return false;
        }

        $length++;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += (int)$numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        return (int)$digits[1] === $result;
    }

    private static function validateRandomKey(string $key): array
    {
        // Chave aleatória PIX tem formato UUID sem hífens
        $cleanKey = preg_replace('/[^a-zA-Z0-9]/', '', $key);

        if (strlen($cleanKey) !== 32) {
            return ['valid' => false, 'message' => 'Chave aleatória deve ter 32 caracteres alfanuméricos.'];
        }

        if (!ctype_alnum($cleanKey)) {
            return ['valid' => false, 'message' => 'Chave aleatória deve conter apenas letras e números.'];
        }

        return ['valid' => true, 'message' => 'Chave aleatória válida.'];
    }
}
