<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Contract\AccountWithdrawRepositoryInterface;

/**
 * Serviço responsável por gerar IDs únicos de transação
 * 
 * Centraliza a lógica de geração de identificadores únicos para transações,
 * garantindo unicidade através de verificação no repositório
 */
class TransactionIdService
{
    public function __construct(
        private readonly AccountWithdrawRepositoryInterface $accountWithdrawRepository
    ) {
    }

    /**
     * Gera um ID de transação único
     * 
     * @return string ID de transação no formato TXN_UNIQID_TIMESTAMP
     */
    public function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN_' . strtoupper(uniqid()) . '_' . time();
        } while ($this->transactionExists($transactionId));

        return $transactionId;
    }

    /**
     * Gera um ID de transação único com prefixo customizado
     * 
     * @param string $prefix Prefixo personalizado
     * @return string ID de transação personalizado
     */
    public function generateCustomTransactionId(string $prefix): string
    {
        do {
            $transactionId = strtoupper($prefix) . '_' . strtoupper(uniqid()) . '_' . time();
        } while ($this->transactionExists($transactionId));

        return $transactionId;
    }

    /**
     * Gera um ID de transação para PIX
     * 
     * @return string ID de transação PIX
     */
    public function generatePixTransactionId(): string
    {
        return $this->generateCustomTransactionId('PIX');
    }

    /**
     * Gera um ID de transação para transferência bancária
     * 
     * @return string ID de transação bancária
     */
    public function generateBankTransferTransactionId(): string
    {
        return $this->generateCustomTransactionId('TED');
    }

    /**
     * Verifica se um ID de transação já existe
     * 
     * @param string $transactionId ID da transação a verificar
     * @return bool Verdadeiro se já existe
     */
    private function transactionExists(string $transactionId): bool
    {
        return $this->accountWithdrawRepository->findByTransactionId($transactionId) !== null;
    }

    /**
     * Valida formato de ID de transação
     * 
     * @param string $transactionId ID da transação
     * @return bool Verdadeiro se o formato é válido
     */
    public function isValidTransactionIdFormat(string $transactionId): bool
    {
        // Formato: PREFIXO_UNIQID_TIMESTAMP
        return preg_match('/^[A-Z]+_[A-Z0-9]+_\d+$/', $transactionId) === 1;
    }

    /**
     * Extrai o timestamp de um ID de transação
     * 
     * @param string $transactionId ID da transação
     * @return int|null Timestamp ou null se inválido
     */
    public function extractTimestamp(string $transactionId): ?int
    {
        $parts = explode('_', $transactionId);
        
        if (count($parts) >= 3) {
            $timestamp = end($parts);
            return is_numeric($timestamp) ? (int) $timestamp : null;
        }
        
        return null;
    }

    /**
     * Extrai o prefixo de um ID de transação
     * 
     * @param string $transactionId ID da transação
     * @return string|null Prefixo ou null se inválido
     */
    public function extractPrefix(string $transactionId): ?string
    {
        $parts = explode('_', $transactionId);
        
        return count($parts) >= 3 ? $parts[0] : null;
    }
}