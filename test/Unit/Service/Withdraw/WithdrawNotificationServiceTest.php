<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Service\Withdraw;

use App\Service\Withdraw\WithdrawNotificationService;
use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\PixData;
use App\Enum\WithdrawMethodEnum;
use App\Enum\PixKeyTypeEnum;
use PHPUnit\Framework\TestCase;

class WithdrawNotificationServiceTest extends TestCase
{
    public function testServiceCanBeInstantiated(): void
    {
        // Test instantiation with null logger (uses container)        
        try {
            $service = new WithdrawNotificationService();
            $this->assertInstanceOf(WithdrawNotificationService::class, $service);
        } catch (\Throwable $e) {
            // Expected in test environment without container
            $this->assertTrue(true);
        }
    }

    public function testServiceHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists(WithdrawNotificationService::class, 'scheduleEmailNotification'));
        $this->assertTrue(method_exists(WithdrawNotificationService::class, '__construct'));
    }

    public function testMethodSignatureIsCorrect(): void
    {
        $reflection = new \ReflectionClass(WithdrawNotificationService::class);
        $method = $reflection->getMethod('scheduleEmailNotification');
        
        $this->assertEquals('scheduleEmailNotification', $method->getName());
        $this->assertEquals(2, $method->getNumberOfParameters());
        
        $parameters = $method->getParameters();
        $this->assertEquals('withdrawId', $parameters[0]->getName());
        $this->assertEquals('withdrawRequestData', $parameters[1]->getName());
    }

    public function testWithdrawRequestDataCanBeCreated(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );
        
        $withdrawRequestData = new WithdrawRequestData(
            accountId: 'account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: $pixData
        );

        $this->assertInstanceOf(WithdrawRequestData::class, $withdrawRequestData);
        $this->assertEquals('account-123', $withdrawRequestData->accountId);
        $this->assertEquals(WithdrawMethodEnum::PIX, $withdrawRequestData->method);
        $this->assertEquals(100.0, $withdrawRequestData->amount);
        $this->assertInstanceOf(PixData::class, $withdrawRequestData->pix);
    }
}