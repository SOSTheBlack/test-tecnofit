<?php

declare(strict_types=1);

namespace Test\Unit\Repository;

use App\Repository\BaseRepository;
use App\Repository\Contract\BaseRepositoryInterface;
use Hyperf\DbConnection\Model\Model;
use PHPUnit\Framework\TestCase;
use Mockery;

class BaseRepositoryTest extends TestCase
{
    private BaseRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a concrete implementation for testing
        $this->repository = new class() extends BaseRepository {
            private Model $mockModel;
            
            public function setMockModel(Model $model): void
            {
                $this->mockModel = $model;
            }
            
            protected function getModel(): Model
            {
                return $this->mockModel;
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testImplementsBaseRepositoryInterface(): void
    {
        $this->assertInstanceOf(BaseRepositoryInterface::class, $this->repository);
    }

    public function testRepositoryCanBeInstantiated(): void
    {
        $this->assertInstanceOf(BaseRepository::class, $this->repository);
    }
    
    public function testRepositoryHasCorrectMethods(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findById'));
        $this->assertTrue(method_exists($this->repository, 'findByIdOrFail'));
        $this->assertTrue(method_exists($this->repository, 'findBy'));
        $this->assertTrue(method_exists($this->repository, 'findOneBy'));
        $this->assertTrue(method_exists($this->repository, 'findAll'));
        $this->assertTrue(method_exists($this->repository, 'create'));
        $this->assertTrue(method_exists($this->repository, 'update'));
        $this->assertTrue(method_exists($this->repository, 'delete'));
        $this->assertTrue(method_exists($this->repository, 'count'));
        $this->assertTrue(method_exists($this->repository, 'exists'));
    }
}
