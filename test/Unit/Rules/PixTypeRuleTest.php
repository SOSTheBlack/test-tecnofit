<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Rules;

use App\Rules\PixTypeRule;
use PHPUnit\Framework\TestCase;

class PixTypeRuleTest extends TestCase
{
    private PixTypeRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new PixTypeRule();
    }

    public function testPassesWithValidType(): void
    {
        $this->assertTrue($this->rule->passes('pix.type', 'email'));
    }

    /**
     * @dataProvider invalidTypeProvider
     */
    public function testFailsWithInvalidType(mixed $value): void
    {
        $this->assertFalse($this->rule->passes('pix.type', $value));
    }

    public function invalidTypeProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'invalid type' => ['invalid'],
            'cpf' => ['cpf'], // Not yet supported in current enum
            'cnpj' => ['cnpj'], // Not yet supported in current enum
            'phone' => ['phone'], // Not yet supported in current enum
            'random_key' => ['random_key'], // Not yet supported in current enum
            'uppercase' => ['EMAIL'], // Case sensitive
            'numeric' => [123],
            'array' => [['email']],
            'object' => [(object)['type' => 'email']],
        ];
    }

    public function testMessage(): void
    {
        $message = $this->rule->message();
        
        $this->assertIsString($message);
        $this->assertStringContainsString('Tipo de chave PIX inválido', $message);
        $this->assertStringContainsString('email', $message); // Should contain available types
    }

    public function testMessageContainsAvailableTypes(): void
    {
        $message = $this->rule->message();
        
        // Should mention available types from enum
        $this->assertStringContainsString('email', $message);
        $this->assertStringContainsString('Use:', $message);
    }

    public function testImplementsRuleInterface(): void
    {
        $this->assertInstanceOf(\Hyperf\Validation\Contract\Rule::class, $this->rule);
    }

    public function testPassesMethodSignature(): void
    {
        // Test that the method signature is correct
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
        // Test that the method signature is correct
        $reflection = new \ReflectionMethod($this->rule, 'message');
        
        $this->assertEquals('string', $reflection->getReturnType()?->getName());
        $this->assertCount(0, $reflection->getParameters());
    }
}