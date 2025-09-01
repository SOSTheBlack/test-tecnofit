<?php

declare(strict_types=1);

namespace App\DataTransfer\Account\Balance;

use Carbon\Carbon;

readonly class WithdrawResultData
{
    // Error code constants
    public const ERROR_ACCOUNT_NOT_FOUND = 'ACCOUNT_NOT_FOUND';
    public const ERROR_INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    public const ERROR_VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const ERROR_UNAUTHORIZED = 'UNAUTHORIZED';
    public const ERROR_FORBIDDEN = 'FORBIDDEN';
    public const ERROR_PROCESSING_ERROR = 'PROCESSING_ERROR';
    public const ERROR_DEBIT_ERROR = 'DEBIT_ERROR';
    public const ERROR_INTERNAL_ERROR = 'INTERNAL_ERROR';

    public function __construct(
        public bool $success,
        public string $message,
        public ?string $errorCode = null,
        public ?array $data = null,
        public ?string $transactionId = null,
        public ?Carbon $processedAt = null,
        public ?array $errors = null,
    ) {
    }

    public static function success(
        array $data,
        string $transactionId,
        string $message = 'Saque processado com sucesso.',
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            transactionId: $transactionId,
            processedAt: timezone()->now(),
        );
    }

    public static function scheduled(
        array $data,
        string $transactionId,
        string $message = 'Saque agendado com sucesso.',
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            transactionId: $transactionId,
            processedAt: timezone()->now(),
        );
    }

    public static function validationError(array $validationErrors): self
    {
        return new self(
            success: false,
            message: 'Dados inválidos fornecidos.',
            errorCode: self::ERROR_VALIDATION_ERROR,
            errors: $validationErrors,
        );
    }

    public static function insufficientBalance(string $message = 'Saldo insuficiente para realizar o saque.'): self
    {
        return new self(
            success: false,
            message: $message,
            errorCode: self::ERROR_INSUFFICIENT_BALANCE,
        );
    }

    public static function processingError(string $message = 'Erro interno ao processar o saque.', ?array $errors = null): self
    {
        return new self(
            success: false,
            message: $message,
            errorCode: self::ERROR_PROCESSING_ERROR,
            errors: $errors,
        );
    }

    public static function debitError(string $message = 'Erro ao debitar valor da conta.'): self
    {
        return new self(
            success: false,
            message: $message,
            errorCode: self::ERROR_DEBIT_ERROR,
        );
    }

    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->errorCode) {
            $result['error_code'] = $this->errorCode;
        }

        if ($this->errors) {
            $result['errors'] = $this->errors;
        }

        if ($this->data) {
            $result['data'] = $this->data;
        }

        if ($this->transactionId) {
            $result['transaction_id'] = $this->transactionId;
        }

        if ($this->processedAt) {
            $result['processed_at'] = $this->processedAt->toISOString();
        }

        return $result;
    }

    public function toJsonResponse(): array
    {
        return $this->toArray();
    }

    public function getHttpStatusCode(): int
    {
        if ($this->success) {
            return 200;
        }

        return match ($this->errorCode) {
            self::ERROR_ACCOUNT_NOT_FOUND => 404,
            self::ERROR_INSUFFICIENT_BALANCE => 422,
            self::ERROR_VALIDATION_ERROR => 422,
            self::ERROR_UNAUTHORIZED => 401,
            self::ERROR_FORBIDDEN => 403,
            self::ERROR_PROCESSING_ERROR => 500,
            self::ERROR_DEBIT_ERROR => 500,
            self::ERROR_INTERNAL_ERROR => 500,
            default => 500,
        };
    }
}
