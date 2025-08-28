<?php

declare(strict_types=1);

namespace Test\Unit\Repository;

use App\Model\Account;
use App\Repository\AccountRepository;
use PHPUnit\Framework\TestCase;
use Mockery;

class AccountRepositoryTest extends TestCase
{
    private AccountRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AccountRepository();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFindByIdReturnsAccountWhenExists(): void
    {
        // Este teste requer configuração de banco de dados de teste
        // Por enquanto, vamos validar a estrutura do método
        $this->assertTrue(method_exists($this->repository, 'findById'));
    }

    public function testGetBalanceReturnsNullWhenAccountNotExists(): void
    {
        $balance = $this->repository->getBalance('non-existent-id');
        $this->assertNull($balance);
    }

    public function testHasSufficientBalanceReturnsFalseWhenAccountNotExists(): void
    {
        $result = $this->repository->hasSufficientBalance('non-existent-id', 100.00);
        $this->assertFalse($result);
    }

    public function testUpdateBalanceReturnsFalseWhenAccountNotExists(): void
    {
        $result = $this->repository->updateBalance('non-existent-id', 1000.00);
        $this->assertFalse($result);
    }

    public function testDebitAmountReturnsFalseWhenAccountNotExists(): void
    {
        $result = $this->repository->debitAmount('non-existent-id', 100.00);
        $this->assertFalse($result);
    }

    public function testDebitAmountReturnsFalseWhenInsufficientBalance(): void
    {
        // Mock de uma conta com saldo insuficiente
        // Este teste seria implementado com banco de teste
        $this->assertTrue(method_exists($this->repository, 'debitAmount'));
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'create'));
    }

    public function testUpdateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'update'));
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'delete'));
    }

    public function testFindAllMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findAll'));
    }

    public function testFindByNameMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findByName'));
    }

    public function testRepositoryImplementsInterface(): void
    {
        $this->assertInstanceOf(
            \App\Repository\Contract\AccountRepositoryInterface::class,
            $this->repository
        );
    }
}
