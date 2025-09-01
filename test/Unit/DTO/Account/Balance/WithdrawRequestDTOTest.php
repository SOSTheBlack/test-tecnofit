<?php

declare(strict_types=1);

namespace HyperfTest\Unit\DTO\Account\Balance;

use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\PixData;
use App\Enum\WithdrawMethodEnum;
use App\Enum\PixKeyTypeEnum;
use Carbon\Carbon;
use HyperfTest\TestCase;

class WithdrawRequestDTOTest extends TestCase
{
    public function testFromArrayWithSchedule(): void
    {
        $data = [
            'account_id' => 'test-account-id',
            'method' => 'PIX',
            'amount' => 100.50,
            'schedule' => '2025-01-01 10:00:00',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ]
        ];

        $requestData = WithdrawRequestData::fromArray($data);

        $this->assertEquals('test-account-id', $requestData->accountId);
        $this->assertEquals(WithdrawMethodEnum::PIX, $requestData->method);
        $this->assertEquals(100.50, $requestData->amount);
        $this->assertInstanceOf(Carbon::class, $requestData->schedule);
        $this->assertEquals('America/Sao_Paulo', $requestData->schedule->getTimezone()->getName());
    }

    public function testIsScheduledWithFutureDate(): void
    {
        $futureDate = timezone()->now()->addDay();
        
        $requestData = new WithdrawRequestData(
            accountId: 'test-account',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            schedule: $futureDate
        );

        $this->assertTrue($requestData->isScheduled());
    }

    public function testIsScheduledWithPastDate(): void
    {
        $pastDate = timezone()->now()->subDay();
        
        $requestData = new WithdrawRequestData(
            accountId: 'test-account',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            schedule: $pastDate
        );

        $this->assertFalse($requestData->isScheduled());
    }
}