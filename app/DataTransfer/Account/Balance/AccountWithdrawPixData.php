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
    ) {
    }

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
            key: $this->key,
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
                if (! filter_var($this->key, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email PIX inválido.';
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
            PixKeyTypeEnum::EMAIL => $this->key
        };
    }
}
