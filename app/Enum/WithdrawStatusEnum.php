<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Enum para status de saques
 * 
 * Define os possíveis status que um saque pode ter durante seu ciclo de vida,
 * centralizando essas constantes em um local apropriado fora do modelo
 */
enum WithdrawStatusEnum: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case SCHEDULED = 'scheduled';

    /**
     * Obtém rótulo legível do status
     * 
     * @return string Rótulo em português
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'Novo',
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::COMPLETED => 'Concluído',
            self::FAILED => 'Falhou',
            self::CANCELLED => 'Cancelado',
            self::SCHEDULED => 'Agendado',
        };
    }

    /**
     * Verifica se o status indica que o saque está em andamento
     * 
     * @return bool Verdadeiro se está em processamento
     */
    public function isInProgress(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING, self::SCHEDULED]);
    }

    /**
     * Verifica se o status indica que o saque foi finalizado
     * 
     * @return bool Verdadeiro se foi finalizado (sucesso ou falha)
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }

    /**
     * Verifica se o status indica sucesso
     * 
     * @return bool Verdadeiro se foi bem-sucedido
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Verifica se o status indica falha
     * 
     * @return bool Verdadeiro se falhou
     */
    public function isFailed(): bool
    {
        return in_array($this, [self::FAILED, self::CANCELLED]);
    }

    /**
     * Verifica se o saque pode ser cancelado
     * 
     * @return bool Verdadeiro se pode ser cancelado
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::NEW, self::PENDING, self::SCHEDULED]);
    }

    /**
     * Verifica se o saque pode ser reprocessado
     * 
     * @return bool Verdadeiro se pode ser reprocessado
     */
    public function canBeRetried(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Obtém próximos status possíveis
     * 
     * @return array<WithdrawStatusEnum> Lista de status possíveis
     */
    public function getNextPossibleStatuses(): array
    {
        return match ($this) {
            self::NEW => [self::PENDING, self::PROCESSING, self::CANCELLED],
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::FAILED],
            self::SCHEDULED => [self::PENDING, self::CANCELLED],
            self::COMPLETED, self::FAILED, self::CANCELLED => [],
        };
    }

    /**
     * Lista todos os status ativos (que podem ter ações)
     * 
     * @return array<WithdrawStatusEnum>
     */
    public static function getActiveStatuses(): array
    {
        return [self::NEW, self::PENDING, self::PROCESSING, self::SCHEDULED];
    }

    /**
     * Lista todos os status finalizados
     * 
     * @return array<WithdrawStatusEnum>
     */
    public static function getFinalizedStatuses(): array
    {
        return [self::COMPLETED, self::FAILED, self::CANCELLED];
    }

    /**
     * Obtém cor para exibição do status
     * 
     * @return string Código de cor hexadecimal
     */
    public function getColor(): string
    {
        return match ($this) {
            self::NEW => '#6366f1',           // indigo
            self::PENDING => '#f59e0b',       // amber
            self::PROCESSING => '#3b82f6',    // blue
            self::COMPLETED => '#10b981',     // emerald
            self::FAILED => '#ef4444',        // red
            self::CANCELLED => '#6b7280',     // gray
            self::SCHEDULED => '#8b5cf6',     // violet
        };
    }

    /**
     * Obtém ícone para exibição do status
     * 
     * @return string Nome do ícone
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::NEW => 'plus-circle',
            self::PENDING => 'clock',
            self::PROCESSING => 'refresh',
            self::COMPLETED => 'check-circle',
            self::FAILED => 'x-circle',
            self::CANCELLED => 'ban',
            self::SCHEDULED => 'calendar',
        };
    }
}