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
        // Configurar variáveis de ambiente para testes
        if (!getenv('DB_HOST')) {
            putenv('DB_HOST=mysql');
        }
        if (!getenv('DB_DATABASE')) {
            putenv('DB_DATABASE=tecnofit_pix_test');
        }
        if (!getenv('DB_USERNAME')) {
            putenv('DB_USERNAME=root');
        }
        if (!getenv('DB_PASSWORD')) {
            putenv('DB_PASSWORD=root');
        }
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
