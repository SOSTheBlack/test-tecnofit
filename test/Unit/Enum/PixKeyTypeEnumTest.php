<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Enum;

use App\Enum\PixKeyTypeEnum;
use PHPUnit\Framework\TestCase;

class PixKeyTypeEnumTest extends TestCase
{
    public function testHasEmailCase(): void
    {
        $this->assertEquals('email', PixKeyTypeEnum::EMAIL->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('E-mail', PixKeyTypeEnum::EMAIL->getLabel());
    }

    public function testGetAvailableTypes(): void
    {
        $types = PixKeyTypeEnum::getAvailableTypes();
        
        $this->assertIsArray($types);
        $this->assertContains('email', $types);
        $this->assertCount(1, $types);
    }

    public function testGetValues(): void
    {
        $values = PixKeyTypeEnum::getValues();
        
        $this->assertIsArray($values);
        $this->assertEquals(['email'], $values);
    }

    public function testIsValidWithValidType(): void
    {
        $this->assertTrue(PixKeyTypeEnum::isValid('email'));
    }

    public function testIsValidWithInvalidType(): void
    {
        $this->assertFalse(PixKeyTypeEnum::isValid('INVALID'));
        $this->assertFalse(PixKeyTypeEnum::isValid('cpf'));
        $this->assertFalse(PixKeyTypeEnum::isValid('cnpj'));
        $this->assertFalse(PixKeyTypeEnum::isValid('phone'));
        $this->assertFalse(PixKeyTypeEnum::isValid('random_key'));
        $this->assertFalse(PixKeyTypeEnum::isValid(''));
        $this->assertFalse(PixKeyTypeEnum::isValid('EMAIL')); // Case sensitive
    }

    public function testEnumCases(): void
    {
        $cases = PixKeyTypeEnum::cases();
        
        $this->assertCount(1, $cases);
        $this->assertContainsOnlyInstancesOf(PixKeyTypeEnum::class, $cases);
    }

    public function testEnumFromValue(): void
    {
        $enum = PixKeyTypeEnum::from('email');
        
        $this->assertInstanceOf(PixKeyTypeEnum::class, $enum);
        $this->assertEquals(PixKeyTypeEnum::EMAIL, $enum);
    }

    public function testEnumTryFromValidValue(): void
    {
        $enum = PixKeyTypeEnum::tryFrom('email');
        
        $this->assertInstanceOf(PixKeyTypeEnum::class, $enum);
        $this->assertEquals(PixKeyTypeEnum::EMAIL, $enum);
    }

    public function testEnumTryFromInvalidValue(): void
    {
        $enum = PixKeyTypeEnum::tryFrom('cpf');
        
        $this->assertNull($enum);
    }

    /**
     * Test structure that would work with expanded enum
     * This is prepared for future enum expansion as mentioned in instructions
     */
    public function testEnumIsReadyForExpansion(): void
    {
        // Test that the enum structure supports expansion
        $this->assertTrue(method_exists(PixKeyTypeEnum::class, 'getAvailableTypes'));
        $this->assertTrue(method_exists(PixKeyTypeEnum::class, 'getValues'));
        $this->assertTrue(method_exists(PixKeyTypeEnum::class, 'isValid'));
        $this->assertTrue(method_exists(PixKeyTypeEnum::class, 'getLabel'));
        
        // Ensure all current cases have labels
        foreach (PixKeyTypeEnum::cases() as $case) {
            $this->assertIsString($case->getLabel());
            $this->assertNotEmpty($case->getLabel());
        }
    }
}