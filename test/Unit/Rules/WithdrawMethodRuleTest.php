<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Rules;

use App\Rules\WithdrawMethodRule;
use PHPUnit\Framework\TestCase;

class WithdrawMethodRuleTest extends TestCase
{
    private WithdrawMethodRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new WithdrawMethodRule();
    }

    public function testPassesWithValidMethod(): void
    {
        $this->assertTrue($this->rule->passes('method', 'PIX'));
    }

    /**
     * @dataProvider invalidMethodProvider
     */
    public function testFailsWithInvalidMethod(mixed $value): void
    {
        $this->assertFalse($this->rule->passes('method', $value));
    }

    public function invalidMethodProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'invalid method' => ['INVALID'],
            'ted' => ['TED'], // Not yet supported
            'doc' => ['DOC'], // Not yet supported
            'bank_transfer' => ['BANK_TRANSFER'], // Not yet supported
            'lowercase pix' => ['pix'], // Case sensitive
            'mixed case' => ['Pix'],
            'numeric' => [123],
            'array' => [['PIX']],
            'object' => [(object)['method' => 'PIX']],
            'boolean' => [true],
            'float' => [1.5],
        ];
    }

    public function testMessage(): void
    {
        $message = $this->rule->message();
        
        $this->assertIsString($message);
        $this->assertStringContainsString('Método de saque inválido', $message);
        $this->assertStringContainsString('PIX', $message); // Should contain available methods
    }

    public function testMessageContainsAvailableMethods(): void
    {
        $message = $this->rule->message();
        
        // Should mention available methods from enum
        $this->assertStringContainsString('PIX', $message);
        $this->assertStringContainsString('Use:', $message);
    }

    public function testImplementsRuleInterface(): void
    {
        $this->assertInstanceOf(\Hyperf\Validation\Contract\Rule::class, $this->rule);
    }

    public function testPassesMethodSignature(): void
    {
        $reflection = new \ReflectionMethod($this->rule, 'passes');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('attribute', $parameters[0]->getName());
        $this->assertEquals('value', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[0]->getType()?->getName());
        $this->assertEquals('mixed', $parameters[1]->getType()?->getName());
    }

    public function testMessageMethodSignature(): void
    {
        $reflection = new \ReflectionMethod($this->rule, 'message');
        
        $this->assertEquals('string', $reflection->getReturnType()?->getName());
        $this->assertCount(0, $reflection->getParameters());
    }

    public function testValidationLogic(): void
    {
        // Test that the rule delegates to the enum
        $this->assertTrue($this->rule->passes('method', 'PIX'));
        $this->assertFalse($this->rule->passes('method', 'INVALID'));
    }

    public function testMessageConsistency(): void
    {
        // Test that message is consistent regardless of when it's called
        $message1 = $this->rule->message();
        $this->rule->passes('method', 'INVALID');
        $message2 = $this->rule->message();
        
        $this->assertEquals($message1, $message2);
    }

    public function testCaseSensitivity(): void
    {
        // Test that the validation is case sensitive
        $this->assertTrue($this->rule->passes('method', 'PIX'));
        $this->assertFalse($this->rule->passes('method', 'pix'));
        $this->assertFalse($this->rule->passes('method', 'Pix'));
        $this->assertFalse($this->rule->passes('method', 'PIx'));
    }

    public function testWhitespaceHandling(): void
    {
        // Test that whitespace is not trimmed
        $this->assertFalse($this->rule->passes('method', ' PIX'));
        $this->assertFalse($this->rule->passes('method', 'PIX '));
        $this->assertFalse($this->rule->passes('method', ' PIX '));
    }

    public function testDifferentDataTypes(): void
    {
        // Test with various data types
        $this->assertFalse($this->rule->passes('method', 0));
        $this->assertFalse($this->rule->passes('method', 1));
        $this->assertFalse($this->rule->passes('method', true));
        $this->assertFalse($this->rule->passes('method', false));
        $this->assertFalse($this->rule->passes('method', []));
        $this->assertFalse($this->rule->passes('method', new \stdClass()));
    }

    /**
     * Test for future expansion readiness
     */
    public function testExpansionReadiness(): void
    {
        // Verify the rule structure supports easy expansion
        $this->assertTrue(method_exists(\App\Enum\WithdrawMethodEnum::class, 'getValues'));
        $this->assertTrue(method_exists(\App\Enum\WithdrawMethodEnum::class, 'isValid'));
        
        // Message should dynamically include all available methods
        $message = $this->rule->message();
        $availableMethods = \App\Enum\WithdrawMethodEnum::getValues();
        
        foreach ($availableMethods as $method) {
            $this->assertStringContainsString($method, $message);
        }
    }
}