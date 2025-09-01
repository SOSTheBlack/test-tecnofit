<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Enum;

use App\Enum\WithdrawMethodEnum;
use PHPUnit\Framework\TestCase;

class WithdrawMethodEnumTest extends TestCase
{
    public function testHasPixCase(): void
    {
        $this->assertEquals('PIX', WithdrawMethodEnum::PIX->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('PIX', WithdrawMethodEnum::PIX->getLabel());
    }

    public function testGetAvailableMethods(): void
    {
        $methods = WithdrawMethodEnum::getAvailableMethods();
        
        $this->assertIsArray($methods);
        $this->assertContains('PIX', $methods);
        $this->assertCount(1, $methods);
    }

    public function testGetValues(): void
    {
        $values = WithdrawMethodEnum::getValues();
        
        $this->assertIsArray($values);
        $this->assertEquals(['PIX'], $values);
    }

    public function testIsValidWithValidMethod(): void
    {
        $this->assertTrue(WithdrawMethodEnum::isValid('PIX'));
    }

    public function testIsValidWithInvalidMethod(): void
    {
        $this->assertFalse(WithdrawMethodEnum::isValid('INVALID'));
        $this->assertFalse(WithdrawMethodEnum::isValid('TED'));
        $this->assertFalse(WithdrawMethodEnum::isValid('DOC'));
        $this->assertFalse(WithdrawMethodEnum::isValid(''));
        $this->assertFalse(WithdrawMethodEnum::isValid('pix')); // Case sensitive
    }

    public function testEnumCases(): void
    {
        $cases = WithdrawMethodEnum::cases();
        
        $this->assertCount(1, $cases);
        $this->assertContainsOnlyInstancesOf(WithdrawMethodEnum::class, $cases);
    }

    public function testEnumFromValue(): void
    {
        $enum = WithdrawMethodEnum::from('PIX');
        
        $this->assertInstanceOf(WithdrawMethodEnum::class, $enum);
        $this->assertEquals(WithdrawMethodEnum::PIX, $enum);
    }

    public function testEnumTryFromValidValue(): void
    {
        $enum = WithdrawMethodEnum::tryFrom('PIX');
        
        $this->assertInstanceOf(WithdrawMethodEnum::class, $enum);
        $this->assertEquals(WithdrawMethodEnum::PIX, $enum);
    }

    public function testEnumTryFromInvalidValue(): void
    {
        $enum = WithdrawMethodEnum::tryFrom('INVALID');
        
        $this->assertNull($enum);
    }
}