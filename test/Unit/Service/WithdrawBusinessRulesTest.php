<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Service;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\Enum\WithdrawMethodEnum;
use App\Service\WithdrawBusinessRules;
use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class WithdrawBusinessRulesTest extends TestCase
{
    private WithdrawBusinessRules $businessRules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->businessRules = new WithdrawBusinessRules();
    }

    public function testValidateWithdrawRequestWithSufficientBalance(): void
    {
        // Arrange
        $accountData = new AccountData(
            id: 'test-account-123',
            name: 'Test Account',
            balance: 1000.0,
            availableBalance: 1000.0
        );

        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: ['type' => 'email', 'key' => 'test@example.com']
        );

        // Act
        $errors = $this->businessRules->validateWithdrawRequest($accountData, $withdrawRequestData);

        // Assert
        $this->assertEmpty($errors);
    }

    public function testValidateWithdrawRequestWithInsufficientBalance(): void
    {
        // Arrange
        $accountData = new AccountData(
            id: 'test-account-123',
            name: 'Test Account',
            balance: 50.0,
            availableBalance: 50.0
        );

        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: ['type' => 'email', 'key' => 'test@example.com']
        );

        // Act
        $errors = $this->businessRules->validateWithdrawRequest($accountData, $withdrawRequestData);

        // Assert
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Saldo insuficiente', $errors[0]);
    }

    public function testValidateWithdrawRequestWithAmountBelowMinimum(): void
    {
        // Arrange
        $accountData = new AccountData(
            id: 'test-account-123',
            name: 'Test Account',
            balance: 1000.0,
            availableBalance: 1000.0
        );

        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 0.001, // Below minimum
            pix: ['type' => 'email', 'key' => 'test@example.com']
        );

        // Act
        $errors = $this->businessRules->validateWithdrawRequest($accountData, $withdrawRequestData);

        // Assert
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Valor mínimo para saque é R$ 0,01', $errors[0]);
    }

    public function testHasSufficientBalance(): void
    {
        // Arrange
        $accountData = new AccountData(
            id: 'test-account-123',
            name: 'Test Account',
            balance: 1000.0,
            availableBalance: 1000.0
        );

        // Act & Assert
        $this->assertTrue($this->businessRules->hasSufficientBalance($accountData, 500.0));
        $this->assertFalse($this->businessRules->hasSufficientBalance($accountData, 1500.0));
    }

    public function testIsAmountAboveMinimum(): void
    {
        // Act & Assert
        $this->assertTrue($this->businessRules->isAmountAboveMinimum(0.01));
        $this->assertTrue($this->businessRules->isAmountAboveMinimum(100.0));
        $this->assertFalse($this->businessRules->isAmountAboveMinimum(0.001));
    }

    public function testCalculateNewBalanceAfterWithdraw(): void
    {
        // Arrange
        $accountData = new AccountData(
            id: 'test-account-123',
            name: 'Test Account',
            balance: 1000.0,
            availableBalance: 1000.0
        );

        // Act
        $newBalances = $this->businessRules->calculateNewBalanceAfterWithdraw($accountData, 100.0);

        // Assert
        $this->assertEquals(900.0, $newBalances['current_balance']);
        $this->assertEquals(900.0, $newBalances['available_balance']);
    }

    public function testShouldSendEmailNotificationForEmailPix(): void
    {
        // Arrange
        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: ['type' => 'email', 'key' => 'test@example.com']
        );

        // Act & Assert
        $this->assertTrue($this->businessRules->shouldSendEmailNotification($withdrawRequestData));
    }

    public function testShouldNotSendEmailNotificationForNonEmailPix(): void
    {
        // Arrange
        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: ['type' => 'CPF', 'key' => '12345678901']
        );

        // Act & Assert
        $this->assertFalse($this->businessRules->shouldSendEmailNotification($withdrawRequestData));
    }
}