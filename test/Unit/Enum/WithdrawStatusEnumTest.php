<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Enum;

use App\Enum\WithdrawStatusEnum;
use PHPUnit\Framework\TestCase;

class WithdrawStatusEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $this->assertEquals('new', WithdrawStatusEnum::NEW->value);
        $this->assertEquals('pending', WithdrawStatusEnum::PENDING->value);
        $this->assertEquals('processing', WithdrawStatusEnum::PROCESSING->value);
        $this->assertEquals('completed', WithdrawStatusEnum::COMPLETED->value);
        $this->assertEquals('failed', WithdrawStatusEnum::FAILED->value);
        $this->assertEquals('cancelled', WithdrawStatusEnum::CANCELLED->value);
        $this->assertEquals('scheduled', WithdrawStatusEnum::SCHEDULED->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('Novo', WithdrawStatusEnum::NEW->getLabel());
        $this->assertEquals('Pendente', WithdrawStatusEnum::PENDING->getLabel());
        $this->assertEquals('Processando', WithdrawStatusEnum::PROCESSING->getLabel());
        $this->assertEquals('Concluído', WithdrawStatusEnum::COMPLETED->getLabel());
        $this->assertEquals('Falhou', WithdrawStatusEnum::FAILED->getLabel());
        $this->assertEquals('Cancelado', WithdrawStatusEnum::CANCELLED->getLabel());
        $this->assertEquals('Agendado', WithdrawStatusEnum::SCHEDULED->getLabel());
    }

    public function testIsInProgress(): void
    {
        $this->assertFalse(WithdrawStatusEnum::NEW->isInProgress());
        $this->assertTrue(WithdrawStatusEnum::PENDING->isInProgress());
        $this->assertTrue(WithdrawStatusEnum::PROCESSING->isInProgress());
        $this->assertFalse(WithdrawStatusEnum::COMPLETED->isInProgress());
        $this->assertFalse(WithdrawStatusEnum::FAILED->isInProgress());
        $this->assertFalse(WithdrawStatusEnum::CANCELLED->isInProgress());
        $this->assertTrue(WithdrawStatusEnum::SCHEDULED->isInProgress());
    }

    public function testIsFinalized(): void
    {
        $this->assertFalse(WithdrawStatusEnum::NEW->isFinalized());
        $this->assertFalse(WithdrawStatusEnum::PENDING->isFinalized());
        $this->assertFalse(WithdrawStatusEnum::PROCESSING->isFinalized());
        $this->assertTrue(WithdrawStatusEnum::COMPLETED->isFinalized());
        $this->assertTrue(WithdrawStatusEnum::FAILED->isFinalized());
        $this->assertTrue(WithdrawStatusEnum::CANCELLED->isFinalized());
        $this->assertFalse(WithdrawStatusEnum::SCHEDULED->isFinalized());
    }

    public function testIsSuccessful(): void
    {
        $this->assertFalse(WithdrawStatusEnum::NEW->isSuccessful());
        $this->assertFalse(WithdrawStatusEnum::PENDING->isSuccessful());
        $this->assertFalse(WithdrawStatusEnum::PROCESSING->isSuccessful());
        $this->assertTrue(WithdrawStatusEnum::COMPLETED->isSuccessful());
        $this->assertFalse(WithdrawStatusEnum::FAILED->isSuccessful());
        $this->assertFalse(WithdrawStatusEnum::CANCELLED->isSuccessful());
        $this->assertFalse(WithdrawStatusEnum::SCHEDULED->isSuccessful());
    }

    public function testIsFailed(): void
    {
        $this->assertFalse(WithdrawStatusEnum::NEW->isFailed());
        $this->assertFalse(WithdrawStatusEnum::PENDING->isFailed());
        $this->assertFalse(WithdrawStatusEnum::PROCESSING->isFailed());
        $this->assertFalse(WithdrawStatusEnum::COMPLETED->isFailed());
        $this->assertTrue(WithdrawStatusEnum::FAILED->isFailed());
        $this->assertTrue(WithdrawStatusEnum::CANCELLED->isFailed());
        $this->assertFalse(WithdrawStatusEnum::SCHEDULED->isFailed());
    }

    public function testCanBeCancelled(): void
    {
        $this->assertTrue(WithdrawStatusEnum::NEW->canBeCancelled());
        $this->assertTrue(WithdrawStatusEnum::PENDING->canBeCancelled());
        $this->assertFalse(WithdrawStatusEnum::PROCESSING->canBeCancelled());
        $this->assertFalse(WithdrawStatusEnum::COMPLETED->canBeCancelled());
        $this->assertFalse(WithdrawStatusEnum::FAILED->canBeCancelled());
        $this->assertFalse(WithdrawStatusEnum::CANCELLED->canBeCancelled());
        $this->assertTrue(WithdrawStatusEnum::SCHEDULED->canBeCancelled());
    }

    public function testCanBeRetried(): void
    {
        $this->assertFalse(WithdrawStatusEnum::NEW->canBeRetried());
        $this->assertFalse(WithdrawStatusEnum::PENDING->canBeRetried());
        $this->assertFalse(WithdrawStatusEnum::PROCESSING->canBeRetried());
        $this->assertFalse(WithdrawStatusEnum::COMPLETED->canBeRetried());
        $this->assertTrue(WithdrawStatusEnum::FAILED->canBeRetried());
        $this->assertFalse(WithdrawStatusEnum::CANCELLED->canBeRetried());
        $this->assertFalse(WithdrawStatusEnum::SCHEDULED->canBeRetried());
    }

    public function testGetNextPossibleStatuses(): void
    {
        $this->assertEquals(
            [WithdrawStatusEnum::PENDING, WithdrawStatusEnum::PROCESSING, WithdrawStatusEnum::CANCELLED],
            WithdrawStatusEnum::NEW->getNextPossibleStatuses()
        );

        $this->assertEquals(
            [WithdrawStatusEnum::PROCESSING, WithdrawStatusEnum::CANCELLED],
            WithdrawStatusEnum::PENDING->getNextPossibleStatuses()
        );

        $this->assertEquals(
            [WithdrawStatusEnum::COMPLETED, WithdrawStatusEnum::FAILED],
            WithdrawStatusEnum::PROCESSING->getNextPossibleStatuses()
        );

        $this->assertEquals(
            [WithdrawStatusEnum::PENDING, WithdrawStatusEnum::CANCELLED],
            WithdrawStatusEnum::SCHEDULED->getNextPossibleStatuses()
        );

        $this->assertEquals([], WithdrawStatusEnum::COMPLETED->getNextPossibleStatuses());
        $this->assertEquals([], WithdrawStatusEnum::FAILED->getNextPossibleStatuses());
        $this->assertEquals([], WithdrawStatusEnum::CANCELLED->getNextPossibleStatuses());
    }

    public function testGetActiveStatuses(): void
    {
        $activeStatuses = WithdrawStatusEnum::getActiveStatuses();

        $this->assertContains(WithdrawStatusEnum::NEW, $activeStatuses);
        $this->assertContains(WithdrawStatusEnum::PENDING, $activeStatuses);
        $this->assertContains(WithdrawStatusEnum::PROCESSING, $activeStatuses);
        $this->assertContains(WithdrawStatusEnum::SCHEDULED, $activeStatuses);
        $this->assertNotContains(WithdrawStatusEnum::COMPLETED, $activeStatuses);
        $this->assertNotContains(WithdrawStatusEnum::FAILED, $activeStatuses);
        $this->assertNotContains(WithdrawStatusEnum::CANCELLED, $activeStatuses);
    }

    public function testGetFinalizedStatuses(): void
    {
        $finalizedStatuses = WithdrawStatusEnum::getFinalizedStatuses();

        $this->assertContains(WithdrawStatusEnum::COMPLETED, $finalizedStatuses);
        $this->assertContains(WithdrawStatusEnum::FAILED, $finalizedStatuses);
        $this->assertContains(WithdrawStatusEnum::CANCELLED, $finalizedStatuses);
        $this->assertNotContains(WithdrawStatusEnum::NEW, $finalizedStatuses);
        $this->assertNotContains(WithdrawStatusEnum::PENDING, $finalizedStatuses);
        $this->assertNotContains(WithdrawStatusEnum::PROCESSING, $finalizedStatuses);
        $this->assertNotContains(WithdrawStatusEnum::SCHEDULED, $finalizedStatuses);
    }

    public function testGetColor(): void
    {
        $this->assertEquals('#6366f1', WithdrawStatusEnum::NEW->getColor());
        $this->assertEquals('#f59e0b', WithdrawStatusEnum::PENDING->getColor());
        $this->assertEquals('#3b82f6', WithdrawStatusEnum::PROCESSING->getColor());
        $this->assertEquals('#10b981', WithdrawStatusEnum::COMPLETED->getColor());
        $this->assertEquals('#ef4444', WithdrawStatusEnum::FAILED->getColor());
        $this->assertEquals('#6b7280', WithdrawStatusEnum::CANCELLED->getColor());
        $this->assertEquals('#8b5cf6', WithdrawStatusEnum::SCHEDULED->getColor());
        
        // Test color format
        foreach (WithdrawStatusEnum::cases() as $status) {
            $color = $status->getColor();
            $this->assertRegExp('/^#[a-f0-9]{6}$/i', $color, "Color for {$status->value} should be valid hex");
        }
    }

    public function testGetIcon(): void
    {
        $this->assertEquals('plus-circle', WithdrawStatusEnum::NEW->getIcon());
        $this->assertEquals('clock', WithdrawStatusEnum::PENDING->getIcon());
        $this->assertEquals('refresh', WithdrawStatusEnum::PROCESSING->getIcon());
        $this->assertEquals('check-circle', WithdrawStatusEnum::COMPLETED->getIcon());
        $this->assertEquals('x-circle', WithdrawStatusEnum::FAILED->getIcon());
        $this->assertEquals('ban', WithdrawStatusEnum::CANCELLED->getIcon());
        $this->assertEquals('calendar', WithdrawStatusEnum::SCHEDULED->getIcon());
        
        // Test all icons are non-empty strings
        foreach (WithdrawStatusEnum::cases() as $status) {
            $icon = $status->getIcon();
            $this->assertIsString($icon);
            $this->assertNotEmpty($icon);
        }
    }

    public function testEnumFromValue(): void
    {
        foreach (WithdrawStatusEnum::cases() as $expectedStatus) {
            $status = WithdrawStatusEnum::from($expectedStatus->value);
            $this->assertEquals($expectedStatus, $status);
        }
    }

    public function testEnumTryFromValidValue(): void
    {
        foreach (WithdrawStatusEnum::cases() as $expectedStatus) {
            $status = WithdrawStatusEnum::tryFrom($expectedStatus->value);
            $this->assertEquals($expectedStatus, $status);
        }
    }

    public function testEnumTryFromInvalidValue(): void
    {
        $status = WithdrawStatusEnum::tryFrom('invalid');
        $this->assertNull($status);
    }

    public function testAllCasesCount(): void
    {
        $this->assertCount(7, WithdrawStatusEnum::cases());
    }

    public function testStatusWorkflow(): void
    {
        // Test typical workflow from NEW to COMPLETED
        $status = WithdrawStatusEnum::NEW;
        $this->assertTrue($status->canBeCancelled());
        $this->assertFalse($status->isInProgress());
        $this->assertFalse($status->isFinalized());

        // Move to PENDING
        $this->assertContains(WithdrawStatusEnum::PENDING, $status->getNextPossibleStatuses());
        
        $status = WithdrawStatusEnum::PENDING;
        $this->assertTrue($status->isInProgress());
        $this->assertTrue($status->canBeCancelled());
        
        // Move to PROCESSING
        $this->assertContains(WithdrawStatusEnum::PROCESSING, $status->getNextPossibleStatuses());
        
        $status = WithdrawStatusEnum::PROCESSING;
        $this->assertTrue($status->isInProgress());
        $this->assertFalse($status->canBeCancelled());
        
        // Move to COMPLETED
        $this->assertContains(WithdrawStatusEnum::COMPLETED, $status->getNextPossibleStatuses());
        
        $status = WithdrawStatusEnum::COMPLETED;
        $this->assertFalse($status->isInProgress());
        $this->assertTrue($status->isFinalized());
        $this->assertTrue($status->isSuccessful());
        $this->assertFalse($status->canBeRetried());
        $this->assertEmpty($status->getNextPossibleStatuses());
    }
}