<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Rules;

use App\Rules\PixKeyRule;
use PHPUnit\Framework\TestCase;

class PixKeyRuleTest extends TestCase
{
    private PixKeyRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new PixKeyRule();
    }

    /**
     * @dataProvider validEmailProvider
     */
    public function testPassesWithValidEmail(string $email): void
    {
        $this->assertTrue($this->rule->passes('pix.key', $email));
    }

    public function validEmailProvider(): array
    {
        return [
            'simple email' => ['test@example.com'],
            'email with subdomain' => ['user@mail.example.com'],
            'email with numbers' => ['user123@example.com'],
            'email with dots' => ['user.name@example.com'],
            'email with plus' => ['user+tag@example.com'],
            'email with dash' => ['user-name@example.com'],
        ];
    }

    /**
     * @dataProvider validRandomKeyProvider
     */
    public function testPassesWithValidRandomKey(string $key): void
    {
        $this->assertTrue($this->rule->passes('pix.key', $key));
    }

    public function validRandomKeyProvider(): array
    {
        return [
            'random key alphanumeric' => ['abc123def456ghi789jkl012mno345pq'],
            'random key all letters' => ['abcdefghijklmnopqrstuvwxyzABCDEF'],
            'random key all numbers' => ['01234567890123456789012345678901'],
            'random key mixed case' => ['AbC123DeF456GhI789JkL012MnO345Pq'],
        ];
    }

    public function testEmptyValueMessage(): void
    {
        $this->rule->passes('pix.key', '');
        $message = $this->rule->message();
        
        $this->assertStringContainsString('vazia', $message);
    }

    public function testTooLongValueMessage(): void
    {
        $this->rule->passes('pix.key', str_repeat('a', 256));
        $message = $this->rule->message();
        
        $this->assertStringContainsString('255 caracteres', $message);
    }

    public function testInvalidFormatMessage(): void
    {
        $this->rule->passes('pix.key', 'invalid-format');
        $message = $this->rule->message();
        
        $this->assertStringContainsString('inválido', $message);
    }

    public function testDefaultMessage(): void
    {
        $message = $this->rule->message();
        
        $this->assertIsString($message);
        $this->assertStringContainsString('PIX', $message);
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

    /**
     * Test email validation edge cases
     */
    public function testEmailValidationEdgeCases(): void
    {
        $validEmails = [
            'simple@example.com',
            'with.dots@example.com',
            'with+plus@example.com',
            'with-dash@example.com',
            'user@subdomain.example.com',
            '123@example.com',
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                $this->rule->passes('pix.key', $email),
                "Email {$email} should be valid"
            );
        }

        $invalidEmails = [
            'invalid',
            '@example.com',
            'user@',
            'user..double.dot@example.com',
            'user@.example.com',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->rule->passes('pix.key', $email),
                "Email {$email} should be invalid"
            );
        }
    }
}