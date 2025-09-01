<?php

declare(strict_types=1);

namespace HyperfTest\Unit\DTO\Account\Balance;

use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\PixData;
use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\Enum\WithdrawMethodEnum;
use App\Enum\PixKeyTypeEnum;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Helper\TimezoneHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Mockery;

class WithdrawRequestDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $pixData = new PixData(PixKeyTypeEnum::EMAIL, 'test@example.com');
        $schedule = Carbon::now()->addDay();
        
        $withdrawRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: $pixData,
            schedule: $schedule,
            metadata: ['test' => 'data'],
            id: 'withdraw-123'
        );

        $this->assertEquals('test-account-123', $withdrawRequest->accountId);
        $this->assertEquals(WithdrawMethodEnum::PIX, $withdrawRequest->method);
        $this->assertEquals(100.50, $withdrawRequest->amount);
        $this->assertEquals($pixData, $withdrawRequest->pix);
        $this->assertEquals($schedule, $withdrawRequest->schedule);
        $this->assertEquals(['test' => 'data'], $withdrawRequest->metadata);
        $this->assertEquals('withdraw-123', $withdrawRequest->id);
    }

    public function testFromArray(): void
    {
        $data = [
            'account_id' => 'test-account-123',
            'method' => 'PIX',
            'amount' => 100.50,
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'schedule' => '2025-01-15 14:30:00',
            'metadata' => ['test' => 'data']
        ];

        $withdrawRequest = WithdrawRequestData::fromArray($data);

        $this->assertEquals('test-account-123', $withdrawRequest->accountId);
        $this->assertEquals(WithdrawMethodEnum::PIX, $withdrawRequest->method);
        $this->assertEquals(100.50, $withdrawRequest->amount);
        $this->assertInstanceOf(PixData::class, $withdrawRequest->pix);
        $this->assertEquals('test@example.com', $withdrawRequest->pix->key);
        $this->assertInstanceOf(Carbon::class, $withdrawRequest->schedule);
        $this->assertEquals(['test' => 'data'], $withdrawRequest->metadata);
    }

    public function testFromArrayWithoutOptionalFields(): void
    {
        $data = [
            'account_id' => 'test-account-123',
            'method' => 'PIX',
            'amount' => 100.50
        ];

        $withdrawRequest = WithdrawRequestData::fromArray($data);

        $this->assertEquals('test-account-123', $withdrawRequest->accountId);
        $this->assertEquals(WithdrawMethodEnum::PIX, $withdrawRequest->method);
        $this->assertEquals(100.50, $withdrawRequest->amount);
        $this->assertNull($withdrawRequest->pix);
        $this->assertNull($withdrawRequest->schedule);
        $this->assertNull($withdrawRequest->metadata);
    }

    public function testIsScheduledWithFutureDate(): void
    {
        // Set fixed now for testing
        $now = Carbon::create(2025, 1, 15, 12, 0, 0);
        Carbon::setTestNow($now);

        $futureDate = Carbon::create(2025, 1, 16, 12, 0, 0);
        
        $withdrawRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            schedule: $futureDate
        );

        $this->assertTrue($withdrawRequest->isScheduled());
    }

    public function testIsScheduledWithNullSchedule(): void
    {
        $withdrawRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            schedule: null
        );

        $this->assertFalse($withdrawRequest->isScheduled());
    }

    public function testIsPixMethod(): void
    {
        $pixRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50
        );

        $this->assertTrue($pixRequest->isPixMethod());
    }

    public function testHasPixData(): void
    {
        $pixData = new PixData(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $withPixData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: $pixData
        );

        $withoutPixData = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: null
        );

        $this->assertTrue($withPixData->hasPixData());
        $this->assertFalse($withoutPixData->hasPixData());
    }

    public function testValidateWithValidData(): void
    {
        $pixData = new PixData(PixKeyTypeEnum::EMAIL, 'test@example.com');
        $futureDate = Carbon::now()->addDay();
        
        $withdrawRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: $pixData,
            schedule: $futureDate
        );

        $errors = $withdrawRequest->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateWithZeroAmount(): void
    {
        $withdrawRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 0
        );

        $errors = $withdrawRequest->validate();

        $this->assertNotEmpty($errors);
        $this->assertContains('O valor deve ser maior que zero.', $errors);
    }

    public function testValidatePixMethodWithoutPixData(): void
    {
        $withdrawRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: null
        );

        $errors = $withdrawRequest->validate();

        $this->assertNotEmpty($errors);
        $this->assertContains('Para saques PIX é necessário informar os dados PIX.', $errors);
    }

    public function testIsValid(): void
    {
        $validRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: new PixData(PixKeyTypeEnum::EMAIL, 'test@example.com')
        );

        $invalidRequest = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 0
        );

        $this->assertTrue($validRequest->isValid());
        $this->assertFalse($invalidRequest->isValid());
    }

    public function testGetPixType(): void
    {
        $pixData = new PixData(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $withPix = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: $pixData
        );

        $withoutPix = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50
        );

        $this->assertEquals('email', $withPix->getPixType());
        $this->assertNull($withoutPix->getPixType());
    }

    public function testGetPixKey(): void
    {
        $pixData = new PixData(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $withPix = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50,
            pix: $pixData
        );

        $withoutPix = new WithdrawRequestData(
            accountId: 'test-account-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.50
        );

        $this->assertEquals('test@example.com', $withPix->getPixKey());
        $this->assertNull($withoutPix->getPixKey());
    }

    public function testReadonlyClass(): void
    {
        $reflection = new \ReflectionClass(WithdrawRequestData::class);
        $this->assertTrue($reflection->isReadOnly());
    }
}