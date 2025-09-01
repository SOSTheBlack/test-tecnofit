<?php

declare(strict_types=1);

namespace HyperfTest\Integration;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use HyperfTest\TestCase;
use Carbon\Carbon;

class WithdrawIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testSuccessfulImmediatePixWithdraw(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 1000.00]);
        
        $withdrawData = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'success@example.com'
            ],
            'amount' => 150.75,
            'schedule' => null
        ];

        // Act
        $response = $this->post("/account/{$account->id}/balance/withdraw", $withdrawData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json();
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('immediate', $responseData['data']['type']);
        $this->assertArrayHasKey('transaction_id', $responseData['data']);
        
        // Verify database changes
        $account->refresh();
        $this->assertEquals(849.25, $account->balance);
        
        // Verify withdraw record
        $withdraw = AccountWithdraw::where('account_id', $account->id)->first();
        $this->assertNotNull($withdraw);
        $this->assertEquals(150.75, $withdraw->amount);
        $this->assertEquals('PIX', $withdraw->method);
        $this->assertEquals('completed', $withdraw->status);
        
        // Verify PIX data
        $pixData = AccountWithdrawPix::where('withdraw_id', $withdraw->id)->first();
        $this->assertNotNull($pixData);
        $this->assertEquals('email', $pixData->type);
        $this->assertEquals('success@example.com', $pixData->key);
    }

    public function testInsufficientBalanceError(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 50.00]);
        
        $withdrawData = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00, // More than balance
            'schedule' => null
        ];

        // Act
        $response = $this->post("/account/{$account->id}/balance/withdraw", $withdrawData);

        // Assert
        $response->assertStatus(422);
        
        $responseData = $response->json();
        $this->assertEquals('validation_error', $responseData['status']);
        $this->assertStringContainsString('Saldo insuficiente', $responseData['message']);
        
        // Verify no changes to account
        $account->refresh();
        $this->assertEquals(50.00, $account->balance);
        
        // Verify no withdraw record created
        $this->assertDatabaseMissing('account_withdraw', [
            'account_id' => $account->id
        ]);
    }

    public function testInvalidPixKeyValidation(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 1000.00]);
        
        $withdrawData = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'invalid-email-format' // Invalid email
            ],
            'amount' => 100.00,
            'schedule' => null
        ];

        // Act
        $response = $this->post("/account/{$account->id}/balance/withdraw", $withdrawData);

        // Assert
        $response->assertStatus(422);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('pix.key', $responseData['errors']);
    }

    public function testMinimumAmountValidation(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 1000.00]);
        
        $withdrawData = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 0.001, // Below minimum (0.01)
            'schedule' => null
        ];

        // Act
        $response = $this->post("/account/{$account->id}/balance/withdraw", $withdrawData);

        // Assert
        $response->assertStatus(422);
        
        $responseData = $response->json();
        $this->assertEquals('validation_error', $responseData['status']);
        $this->assertStringContainsString('Valor mínimo', $responseData['message']);
    }

    public function testConcurrentWithdrawals(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 100.00]);
        
        $withdrawData1 = [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'test1@example.com'],
            'amount' => 60.00,
            'schedule' => null
        ];
        
        $withdrawData2 = [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'test2@example.com'],
            'amount' => 60.00,
            'schedule' => null
        ];

        // Act - Simulate concurrent requests
        $response1 = $this->post("/account/{$account->id}/balance/withdraw", $withdrawData1);
        $response2 = $this->post("/account/{$account->id}/balance/withdraw", $withdrawData2);

        // Assert - One should succeed, one should fail due to insufficient balance
        $this->assertTrue(
            ($response1->status() === 201 && $response2->status() === 422) ||
            ($response1->status() === 422 && $response2->status() === 201)
        );
        
        // Verify account balance is never negative
        $account->refresh();
        $this->assertGreaterThanOrEqual(0, $account->balance);
        
        // Verify only one withdrawal was processed
        $withdrawCount = AccountWithdraw::where('account_id', $account->id)
            ->where('status', 'completed')
            ->count();
        $this->assertEquals(1, $withdrawCount);
    }

    public function testResponseStructure(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 1000.00]);
        
        $withdrawData = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00,
            'schedule' => null
        ];

        // Act
        $response = $this->post("/account/{$account->id}/balance/withdraw", $withdrawData);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'type',
                'transaction_id',
                'amount',
                'method',
                'created_at'
            ]
        ]);
    }
}