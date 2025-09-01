<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\DataTransfer\Account\AccountData;

/**
 * Interface para repositório de contas
 * 
 * Define o contrato para operações de persistência de contas,
 * seguindo o padrão de retornar apenas DTOs
 */
interface AccountRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontra uma conta pelo ID retornando DTO
     * 
     * @param string $accountId ID da conta
     * @return AccountData|null DTO da conta ou null se não encontrada
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando conta não encontrada
     */
    public function findAccountById(string $accountId): ?AccountData;

    /**
     * Encontra uma conta pelo ID ou lança exceção retornando DTO
     * 
     * @param string $accountId ID da conta
     * @return AccountData DTO da conta
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando conta não encontrada
     */
    public function findAccountByIdOrFail(string $accountId): AccountData;

    /**
     * Debita um valor da conta
     * 
     * @param string $accountId ID da conta
     * @param float $amount Valor a ser debitado
     * @return bool Verdadeiro se o débito foi realizado com sucesso
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando conta não encontrada
     */
    public function debitAmount(string $accountId, float $amount): bool;

    /**
     * Credita um valor na conta
     * 
     * @param string $accountId ID da conta
     * @param float $amount Valor a ser creditado
     * @return bool Verdadeiro se o crédito foi realizado com sucesso
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando conta não encontrada
     */
    public function creditAmount(string $accountId, float $amount): bool;

    /**
     * Atualiza informações da conta
     * 
     * @param string $accountId ID da conta
     * @param array $data Dados a serem atualizados
     * @return bool Verdadeiro se a atualização foi bem-sucedida
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException Quando conta não encontrada
     */
    public function updateAccount(string $accountId, array $data): bool;

    /**
     * Cria uma nova conta
     * 
     * @param array $data Dados da conta
     * @return AccountData DTO da conta criada
     * @throws \RuntimeException Quando falha ao criar
     */
    public function createAccount(array $data): AccountData;

    /**
     * Verifica se uma conta existe
     * 
     * @param string $accountId ID da conta
     * @return bool Verdadeiro se a conta existe
     */
    public function accountExists(string $accountId): bool;

    /**
     * Lista contas com filtros opcionais
     * 
     * @param array $criteria Critérios de busca
     * @param int $page Página para paginação
     * @param int $perPage Itens por página
     * @return array Array com dados paginados
     */
    public function listAccounts(array $criteria = [], int $page = 1, int $perPage = 15): array;
}
