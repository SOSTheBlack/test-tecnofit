<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\DataTransfer\Account\Balance\AccountWithdrawPixData;

/**
 * Interface para repositório de saques
 * 
 * Define o contrato para operações de persistência de saques,
 * seguindo o padrão de retornar apenas DTOs
 */
interface AccountWithdrawRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontra um saque pelo ID retornando DTO
     * 
     * @param string $id ID do saque
     * @return AccountWithdrawData|null DTO do saque ou null se não encontrado
     */
    public function findWithdrawById(string $id): ?AccountWithdrawData;

    /**
     * Encontra um saque pelo ID ou lança exceção retornando DTO
     * 
     * @param string $id ID do saque
     * @return AccountWithdrawData DTO do saque
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando saque não encontrado
     */
    public function findWithdrawByIdOrFail(string $id): AccountWithdrawData;

    /**
     * Encontra um saque pelo transaction_id
     * 
     * @param string $transactionId ID da transação
     * @return AccountWithdrawData|null DTO do saque ou null se não encontrado
     */
    public function findByTransactionId(string $transactionId): ?AccountWithdrawData;

    /**
     * Cria um novo saque
     * 
     * @param array $data Dados do saque
     * @return AccountWithdrawData DTO do saque criado
     * @throws \RuntimeException Quando falha ao criar
     */
    public function createWithdraw(array $data): AccountWithdrawData;

    /**
     * Cria dados PIX para um saque
     * 
     * @param string $withdrawId ID do saque
     * @param string $key Chave PIX
     * @param string $type Tipo da chave PIX
     * @return AccountWithdrawPixData DTO dos dados PIX criados
     * @throws \RuntimeException Quando falha ao criar
     */
    public function createPixData(string $withdrawId, string $key, string $type): AccountWithdrawPixData;

    /**
     * Atualiza um saque
     * 
     * @param string $id ID do saque
     * @param array $data Dados a serem atualizados
     * @return bool Verdadeiro se a atualização foi bem-sucedida
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando saque não encontrado
     */
    public function updateWithdraw(string $id, array $data): bool;

    /**
     * Marca um saque como processando
     * 
     * @param string $id ID do saque
     * @return bool Verdadeiro se a atualização foi bem-sucedida
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando saque não encontrado
     */
    public function markAsProcessing(string $id): bool;

    /**
     * Marca um saque como completado
     * 
     * @param string $id ID do saque
     * @param array $metadata Metadados adicionais (opcional)
     * @return bool Verdadeiro se a atualização foi bem-sucedida
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando saque não encontrado
     */
    public function markAsCompleted(string $id, array $metadata = []): bool;

    /**
     * Marca um saque como falhado
     * 
     * @param string $id ID do saque
     * @param string $errorReason Motivo da falha
     * @param array $metadata Metadados adicionais (opcional)
     * @return bool Verdadeiro se a atualização foi bem-sucedida
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando saque não encontrado
     */
    public function markAsFailed(string $id, string $errorReason, array $metadata = []): bool;

    /**
     * Lista saques por conta com filtros opcionais
     * 
     * @param string $accountId ID da conta
     * @param array $criteria Critérios de busca adicionais
     * @param int $page Página para paginação
     * @param int $perPage Itens por página
     * @return array Array com dados paginados
     */
    public function listByAccount(string $accountId, array $criteria = [], int $page = 1, int $perPage = 15): array;

    /**
     * Lista saques agendados prontos para execução
     * 
     * @param int $limit Limite de resultados
     * @return array Lista de DTOs de saques prontos para execução
     */
    public function findScheduledReady(int $limit = 100): array;

    /**
     * Conta saques pendentes por conta
     * 
     * @param string $accountId ID da conta
     * @return int Número de saques pendentes
     */
    public function countPendingByAccount(string $accountId): int;

    /**
     * Obtém total de valores pendentes por conta
     * 
     * @param string $accountId ID da conta
     * @return float Total de valores pendentes
     */
    public function getTotalPendingAmountByAccount(string $accountId): float;
}
