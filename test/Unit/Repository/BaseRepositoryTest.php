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
    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->model = Mockery::mock(Model::class);
        $this->repository = new class($this->model) extends BaseRepository {
            public function __construct(private Model $model)
            {
            }
            
            protected function getModel(): Model
            {
                return $this->model;
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

    public function testFindByIdReturnsModel(): void
    {
        $id = 'test-id';
        $this->model->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn($this->model);

        $result = $this->repository->findById($id);

        $this->assertSame($this->model, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $id = 'test-id';
        $this->model->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn(null);

        $result = $this->repository->findById($id);

        $this->assertNull($result);
    }

    public function testExistsReturnsTrueWhenRecordExists(): void
    {
        $criteria = ['field' => 'value'];
        $query = Mockery::mock();
        
        $this->model->shouldReceive('newQuery')
            ->once()
            ->andReturn($query);
            
        $query->shouldReceive('where')
            ->with('field', 'value')
            ->once()
            ->andReturn($query);
            
        $query->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $result = $this->repository->exists($criteria);

        $this->assertTrue($result);
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $criteria = ['status' => 'active'];
        $query = Mockery::mock();
        
        $this->model->shouldReceive('newQuery')
            ->once()
            ->andReturn($query);
            
        $query->shouldReceive('where')
            ->with('status', 'active')
            ->once()
            ->andReturn($query);
            
        $query->shouldReceive('count')
            ->once()
            ->andReturn(5);

        $result = $this->repository->count($criteria);

        $this->assertEquals(5, $result);
    }
}
