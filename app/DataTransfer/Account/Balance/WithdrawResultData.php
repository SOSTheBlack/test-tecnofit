<?php

declare(strict_types=1);

namespace App\DataTransfer\Account\Balance;

use Carbon\Carbon;

readonly class WithdrawResultData
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $errorCode = null,
        public ?array $data = null,
        public ?string $transactionId = null,
        public ?Carbon $processedAt = null,
        public ?array $errors = null,
    ) {}

    public static function success(
        array $data, 
        string $transactionId, 
        string $message = 'Saque processado com sucesso.'
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            transactionId: $transactionId,
            processedAt: Carbon::now(),
        );
    }

    public static function scheduled(
        array $data, 
        string $transactionId,
        string $message = 'Saque agendado com sucesso.'
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            transactionId: $transactionId,
            processedAt: Carbon::now(),
        );
    }

    public static function error(
        string $errorCode, 
        string $message, 
        ?array $errors = null
    ): self {
        return new self(
            success: false,
            message: $message,
            errorCode: $errorCode,
            errors: $errors,
        );
    }

    public static function validationError(array $validationErrors): self
    {
        return new self(
            success: false,
            message: 'Dados inválidos fornecidos.',
            errorCode: 'VALIDATION_ERROR',
            errors: $validationErrors,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function hasErrors(): bool
    {
        return !$this->success;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
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
            'ACCOUNT_NOT_FOUND' => 404,
            'INSUFFICIENT_BALANCE' => 422,
            'VALIDATION_ERROR' => 422,
            'UNAUTHORIZED' => 401,
            'FORBIDDEN' => 403,
            default => 500,
        };
    }
}
