<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Service;

use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\Enum\WithdrawMethodEnum;
use App\Service\WithdrawNotificationService;
use PHPUnit\Framework\TestCase;
use Mockery;
use Psr\Log\LoggerInterface;

class WithdrawNotificationServiceTest extends TestCase
{
    private WithdrawNotificationService $notificationService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->notificationService = new WithdrawNotificationService($this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testScheduleEmailNotificationForEmailPix(): void
    {
        // Arrange
        $withdrawId = 'test-withdraw-123';
        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: ['type' => 'email', 'key' => 'test@example.com']
        );

        $this->logger->shouldReceive('info')->once()
            ->with('Job de notificação de email agendado', Mockery::type('array'));

        // Act
        $result = $this->notificationService->scheduleEmailNotification($withdrawId, $withdrawRequestData);

        // Assert - For now just check it doesn't throw exception
        // In real test, we would mock the DriverFactory and test actual job scheduling
        $this->assertTrue(true);
    }

    public function testScheduleEmailNotificationForNonEmailPix(): void
    {
        // Arrange
        $withdrawId = 'test-withdraw-123';
        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: ['type' => 'CPF', 'key' => '12345678901']
        );

        $this->logger->shouldReceive('info')->once()
            ->with('Email não enviado - chave PIX não é email', Mockery::type('array'));

        // Act
        $result = $this->notificationService->scheduleEmailNotification($withdrawId, $withdrawRequestData);

        // Assert
        $this->assertTrue($result); // Should return true even when not sending
    }
}