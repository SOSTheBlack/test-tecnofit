<?php

declare(strict_types=1);

namespace HyperfTest;

use Hyperf\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurações específicas para testes
        $this->initializeDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Limpar dados após cada teste
        $this->cleanupDatabase();
    }

    protected function initializeDatabase(): void
    {
        // Inicializar banco de dados para testes
        // Executar migrations, seeders, etc.
    }

    protected function cleanupDatabase(): void
    {
        // Limpar banco de dados após testes
        // Rollback de transações, limpeza de cache, etc.
    }
}
