<?php

declare(strict_types=1);

namespace HyperfTest;

use Hyperf\Testing\TestCase as BaseTestCase;
use Hyperf\DbConnection\Db;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar para usar banco de teste
        $this->configureDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function configureDatabase(): void
    {
        // Configurar conexão de teste se necessário
        putenv('DB_DATABASE=tecnofit_pix_test');
    }

    protected function initializeDatabase(): void
    {
        // Base implementation - override in subclasses if needed
    }

    protected function cleanupDatabase(): void
    {
        // Base implementation - override in subclasses if needed
    }
}
