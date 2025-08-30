<?php

declare(strict_types=1);

namespace Test\Unit\DTO\Account\Balance;

use App\DTO\Account\Balance\PixDataDTO;
use App\DTO\Account\Balance\WithdrawRequestDTO;
use App\Enum\PixKeyTypeEnum;
use App\Enum\WithdrawMethodEnum;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class WithdrawRequestDTOTest extends TestCase
{
    public function testCanCreateFromArray(): void
    {
        $data = [
            'account_id' => 'test-account-123',
            'method' => 'PIX',
            'amount' => 100.50,
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'schedule' => '2024-12-31 10:00:00',
            'metadata' => ['source' => 'app']
        ];

        $dto = WithdrawRequestDTO::fromArray($data);

        $this->assertEquals('test-account-123', $dto->accountId);
        $this->assertEquals(WithdrawMethodEnum::PIX, $dto->method);
        $this->assertEquals(100.50, $dto->amount);
        $this->assertInstanceOf(PixDataDTO::class, $dto->pix);
        $this->assertEquals(PixKeyTypeEnum::EMAIL, $dto->pix->type);
        $this->assertEquals('test@example.com', $dto->pix->key);
        $this->assertInstanceOf(Carbon::class, $dto->schedule);
        $this->assertEquals(['source' => 'app'], $dto->metadata);
    }

    public function testCanCreateFromRequestData(): void
    {
        $requestData = [
            'account_id' => 'test-account-123',
            'method' => 'PIX',
            'amount' => 100.50,
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ]
        ];

        $dto = WithdrawRequestDTO::fromRequestData($requestData);

        $this->assertEquals('test-account-123', $dto->accountId);
        $this->assertEquals(WithdrawMethodEnum::PIX, $dto->method);
        $this->assertEquals(100.50, $dto->amount);
        $this->assertInstanceOf(PixDataDTO::class, $dto->pix);
        $this->assertEquals(PixKeyTypeEnum::EMAIL, $dto->pix->type);
        $this->assertEquals('test@example.com', $dto->pix->key);
    }

    public function testIsImmediateWhenNoSchedule(): void
    {
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0
        );

        $this->assertTrue($dto->isImmediate());
        $this->assertFalse($dto->isScheduled());
    }

    public function testIsScheduledWhenFutureDate(): void
    {
        $futureDate = Carbon::now()->addHour();
        
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            schedule: $futureDate
        );

        $this->assertTrue($dto->isScheduled());
        $this->assertFalse($dto->isImmediate());
    }

    public function testValidationFailsForInvalidAmount(): void
    {
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: -100.0
        );

        $this->assertFalse($dto->isValid());
        $errors = $dto->validate();
        $this->assertContains('O valor deve ser maior que zero.', $errors);
    }

    public function testValidationFailsForPixWithoutData(): void
    {
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0
        );

        $this->assertFalse($dto->isValid());
        $errors = $dto->validate();
        $this->assertContains('Para saques PIX é necessário informar os dados PIX.', $errors);
    }

    public function testValidationFailsForPastSchedule(): void
    {
        $pastDate = Carbon::now()->subHour();
        $pixData = new PixDataDTO(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: $pixData,
            schedule: $pastDate
        );

        $this->assertFalse($dto->isValid());
        $errors = $dto->validate();
        $this->assertContains('A data de agendamento deve ser futura.', $errors);
    }

    public function testValidationPassesForValidData(): void
    {
        $pixData = new PixDataDTO(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: $pixData
        );

        $this->assertTrue($dto->isValid());
        $this->assertEmpty($dto->validate());
    }

    public function testHasPixDataDetection(): void
    {
        $pixData = new PixDataDTO(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $dtoWithPix = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: $pixData
        );

        $dtoWithoutPix = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0
        );

        $this->assertTrue($dtoWithPix->hasPixData());
        $this->assertFalse($dtoWithoutPix->hasPixData());
    }

    public function testPixConvenienceMethods(): void
    {
        $pixData = new PixDataDTO(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: $pixData
        );

        $this->assertEquals('email', $dto->getPixType());
        $this->assertEquals('test@example.com', $dto->getPixKey());
        $this->assertStringContainsString('*', $dto->getMaskedPixKey());
    }

    public function testToArraySerialization(): void
    {
        $schedule = Carbon::parse('2024-12-31 10:00:00');
        $pixData = new PixDataDTO(PixKeyTypeEnum::EMAIL, 'test@example.com');
        
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: $pixData,
            schedule: $schedule,
            metadata: ['test' => 'data']
        );

        $array = $dto->toArray();

        $this->assertEquals('test-123', $array['account_id']);
        $this->assertEquals('PIX', $array['method']);
        $this->assertEquals(100.0, $array['amount']);
        $this->assertEquals(['type' => 'email', 'key' => 'test@example.com'], $array['pix']);
        $this->assertEquals($schedule->toISOString(), $array['schedule']);
        $this->assertEquals(['test' => 'data'], $array['metadata']);
    }

    public function testValidationFailsForInvalidPixEmail(): void
    {
        $pixData = new PixDataDTO(PixKeyTypeEnum::EMAIL, 'invalid-email');
        
        $dto = new WithdrawRequestDTO(
            accountId: 'test-123',
            method: WithdrawMethodEnum::PIX,
            amount: 100.0,
            pix: $pixData
        );

        $this->assertFalse($dto->isValid());
        $errors = $dto->validate();
        $this->assertContains('PIX: Email PIX inválido.', $errors);
    }
}
