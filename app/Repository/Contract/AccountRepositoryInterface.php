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
}
