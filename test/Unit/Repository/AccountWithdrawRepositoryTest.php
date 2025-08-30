<?php

declare(strict_types=1);

namespace Test\Unit\Repository;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Repository\AccountWithdrawRepository;
use App\Enum\WithdrawMethodEnum;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AccountWithdrawRepositoryTest extends TestCase
{
    private AccountWithdrawRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AccountWithdrawRepository();
    }

    public function testCanCreateWithdraw(): void
    {
        $data = [
            'account_id' => 'account-123',
            'method' => WithdrawMethodEnum::PIX->value,
            'amount' => 100.50,
            'scheduled' => false,
            'status' => AccountWithdraw::STATUS_PENDING,
        ];

        $withdraw = $this->repository->create($data);

        $this->assertInstanceOf(AccountWithdraw::class, $withdraw);
        $this->assertEquals('account-123', $withdraw->account_id);
        $this->assertEquals(100.50, $withdraw->amount);
        $this->assertEquals(WithdrawMethodEnum::PIX->value, $withdraw->method);
        $this->assertNotNull($withdraw->id);
        $this->assertNotNull($withdraw->transaction_id);
    }

    public function testCanFindById(): void
    {
        $withdraw = AccountWithdraw::factory()->create();

        $found = $this->repository->findById($withdraw->id);

        $this->assertInstanceOf(AccountWithdraw::class, $found);
        $this->assertEquals($withdraw->id, $found->id);
    }

    public function testCanFindByTransactionId(): void
    {
        $withdraw = AccountWithdraw::factory()->create([
            'transaction_id' => 'TXN_TEST_123'
        ]);

        $found = $this->repository->findByTransactionId('TXN_TEST_123');

        $this->assertInstanceOf(AccountWithdraw::class, $found);
        $this->assertEquals($withdraw->id, $found->id);
    }

    public function testCanUpdateWithdraw(): void
    {
        $withdraw = AccountWithdraw::factory()->create([
            'status' => AccountWithdraw::STATUS_PENDING
        ]);

        $success = $this->repository->update($withdraw->id, [
            'status' => AccountWithdraw::STATUS_COMPLETED
        ]);

        $this->assertTrue($success);
        
        $updated = $this->repository->findById($withdraw->id);
        $this->assertEquals(AccountWithdraw::STATUS_COMPLETED, $updated->status);
    }

    public function testCanDeleteWithdraw(): void
    {
        $withdraw = AccountWithdraw::factory()->create();

        $success = $this->repository->delete($withdraw->id);

        $this->assertTrue($success);
        
        $deleted = $this->repository->findById($withdraw->id);
        $this->assertNull($deleted);
    }

    public function testCanFindByAccountId(): void
    {
        $accountId = 'account-123';
        AccountWithdraw::factory()->count(3)->create(['account_id' => $accountId]);
        AccountWithdraw::factory()->count(2)->create(['account_id' => 'other-account']);

        $withdraws = $this->repository->findByAccountId($accountId);

        $this->assertCount(3, $withdraws);
        foreach ($withdraws as $withdraw) {
            $this->assertEquals($accountId, $withdraw->account_id);
        }
    }

    public function testCanFindPendingByAccountId(): void
    {
        $accountId = 'account-123';
        AccountWithdraw::factory()->count(2)->create([
            'account_id' => $accountId,
            'status' => AccountWithdraw::STATUS_PENDING
        ]);
        AccountWithdraw::factory()->create([
            'account_id' => $accountId,
            'status' => AccountWithdraw::STATUS_COMPLETED
        ]);

        $pendingWithdraws = $this->repository->findPendingByAccountId($accountId);

        $this->assertCount(2, $pendingWithdraws);
        foreach ($pendingWithdraws as $withdraw) {
            $this->assertEquals(AccountWithdraw::STATUS_PENDING, $withdraw->status);
        }
    }

    public function testCanFindScheduledReadyForExecution(): void
    {
        // Saque agendado para o passado (pronto)
        AccountWithdraw::factory()->create([
            'scheduled' => true,
            'scheduled_for' => Carbon::now()->subHour(),
            'status' => AccountWithdraw::STATUS_PENDING
        ]);

        // Saque agendado para o futuro (não pronto)
        AccountWithdraw::factory()->create([
            'scheduled' => true,
            'scheduled_for' => Carbon::now()->addHour(),
            'status' => AccountWithdraw::STATUS_PENDING
        ]);

        $readyWithdraws = $this->repository->findScheduledReadyForExecution();

        $this->assertCount(1, $readyWithdraws);
        $this->assertTrue($readyWithdraws->first()->scheduled_for->isPast());
    }

    public function testCanGetTotalPendingAmountForAccount(): void
    {
        $accountId = 'account-123';
        AccountWithdraw::factory()->create([
            'account_id' => $accountId,
            'status' => AccountWithdraw::STATUS_PENDING,
            'amount' => 100.00
        ]);
        AccountWithdraw::factory()->create([
            'account_id' => $accountId,
            'status' => AccountWithdraw::STATUS_PROCESSING,
            'amount' => 50.00
        ]);
        AccountWithdraw::factory()->create([
            'account_id' => $accountId,
            'status' => AccountWithdraw::STATUS_COMPLETED,
            'amount' => 200.00
        ]);

        $totalPending = $this->repository->getTotalPendingAmountForAccount($accountId);

        $this->assertEquals(150.00, $totalPending);
    }

    public function testCanMarkAsProcessing(): void
    {
        $withdraw = AccountWithdraw::factory()->create([
            'status' => AccountWithdraw::STATUS_PENDING
        ]);

        $success = $this->repository->markAsProcessing($withdraw->id);

        $this->assertTrue($success);
        
        $updated = $this->repository->findById($withdraw->id);
        $this->assertEquals(AccountWithdraw::STATUS_PROCESSING, $updated->status);
    }

    public function testCanMarkAsCompleted(): void
    {
        $withdraw = AccountWithdraw::factory()->create([
            'status' => AccountWithdraw::STATUS_PROCESSING,
            'done' => false
        ]);

        $metadata = ['completed_at' => Carbon::now()->toISOString()];
        $success = $this->repository->markAsCompleted($withdraw->id, $metadata);

        $this->assertTrue($success);
        
        $updated = $this->repository->findById($withdraw->id);
        $this->assertEquals(AccountWithdraw::STATUS_COMPLETED, $updated->status);
        $this->assertTrue($updated->done);
        $this->assertFalse($updated->error);
        $this->assertArrayHasKey('completed_at', $updated->meta);
    }

    public function testCanMarkAsFailed(): void
    {
        $withdraw = AccountWithdraw::factory()->create([
            'status' => AccountWithdraw::STATUS_PROCESSING,
            'error' => false
        ]);

        $errorReason = 'Connection timeout';
        $metadata = ['error_details' => 'Network error occurred'];
        $success = $this->repository->markAsFailed($withdraw->id, $errorReason, $metadata);

        $this->assertTrue($success);
        
        $updated = $this->repository->findById($withdraw->id);
        $this->assertEquals(AccountWithdraw::STATUS_FAILED, $updated->status);
        $this->assertTrue($updated->error);
        $this->assertEquals($errorReason, $updated->error_reason);
        $this->assertArrayHasKey('error_details', $updated->meta);
    }

    public function testCanCancelWithdraw(): void
    {
        $withdraw = AccountWithdraw::factory()->create([
            'status' => AccountWithdraw::STATUS_PENDING
        ]);

        $reason = 'User requested cancellation';
        $success = $this->repository->cancel($withdraw->id, $reason);

        $this->assertTrue($success);
        
        $updated = $this->repository->findById($withdraw->id);
        $this->assertEquals(AccountWithdraw::STATUS_CANCELLED, $updated->status);
        $this->assertEquals($reason, $updated->error_reason);
    }

    public function testCanFindByFilters(): void
    {
        AccountWithdraw::factory()->create([
            'method' => WithdrawMethodEnum::PIX->value,
            'amount' => 100.00,
            'status' => AccountWithdraw::STATUS_PENDING
        ]);
        
        AccountWithdraw::factory()->create([
            'method' => WithdrawMethodEnum::PIX->value,
            'amount' => 200.00,
            'status' => AccountWithdraw::STATUS_COMPLETED
        ]);

        $filters = [
            'method' => WithdrawMethodEnum::PIX->value,
            'min_amount' => 150.00,
            'status' => AccountWithdraw::STATUS_COMPLETED
        ];

        $filtered = $this->repository->findByFilters($filters);

        $this->assertCount(1, $filtered);
        $this->assertEquals(200.00, $filtered->first()->amount);
    }

    public function testCanGetAccountWithdrawStats(): void
    {
        $accountId = 'account-123';
        
        AccountWithdraw::factory()->count(2)->create([
            'account_id' => $accountId,
            'status' => AccountWithdraw::STATUS_PENDING,
            'amount' => 100.00
        ]);
        
        AccountWithdraw::factory()->create([
            'account_id' => $accountId,
            'status' => AccountWithdraw::STATUS_COMPLETED,
            'amount' => 300.00
        ]);

        $stats = $this->repository->getAccountWithdrawStats($accountId);

        $this->assertEquals(3, $stats['total_count']);
        $this->assertEquals(500.00, $stats['total_amount']);
        $this->assertEquals(2, $stats['pending_count']);
        $this->assertEquals(200.00, $stats['pending_amount']);
        $this->assertEquals(1, $stats['completed_count']);
        $this->assertEquals(300.00, $stats['completed_amount']);
    }
}
