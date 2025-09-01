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
     * @dataProvider validCpfProvider
     */
    public function testPassesWithValidCpf(string $cpf): void
    {
        $this->assertTrue($this->rule->passes('pix.key', $cpf));
    }

    public function validCpfProvider(): array
    {
        return [
            'cpf 11 digits' => ['12345678901'],
            'cpf with zeros' => ['00012345678'],
            'cpf all same digits' => ['11111111111'], // Basic format validation only
        ];
    }

    /**
     * @dataProvider validCnpjProvider
     */
    public function testPassesWithValidCnpj(string $cnpj): void
    {
        $this->assertTrue($this->rule->passes('pix.key', $cnpj));
    }

    public function validCnpjProvider(): array
    {
        return [
            'cnpj 14 digits' => ['12345678901234'],
            'cnpj with zeros' => ['00000012345678'],
        ];
    }

    /**
     * @dataProvider validPhoneProvider
     */
    public function testPassesWithValidPhone(string $phone): void
    {
        $this->assertTrue($this->rule->passes('pix.key', $phone));
    }

    public function validPhoneProvider(): array
    {
        return [
            'phone with country code' => ['+5511999999999'],
            'phone without country code' => ['11999999999'],
            'phone with 8 digits' => ['1199999999'],
            'landline phone' => ['1133334444'],
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

    /**
     * @dataProvider invalidKeysProvider
     */
    public function testFailsWithInvalidKeys(mixed $key, string $expectedErrorType): void
    {
        $result = $this->rule->passes('pix.key', $key);
        
        $this->assertFalse($result);
        
        $message = $this->rule->message();
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }

    public function invalidKeysProvider(): array
    {
        return [
            'empty string' => ['', 'empty'],
            'null' => [null, 'empty'],
            'too long string' => [str_repeat('a', 256), 'too_long'],
            'invalid email' => ['invalid-email', 'invalid_format'],
            'cpf too short' => ['123456789', 'invalid_format'],
            'cpf too long' => ['123456789012', 'invalid_format'],
            'cnpj too short' => ['12345678901', 'invalid_format'],
            'cnpj too long' => ['123456789012345', 'invalid_format'],
            'random key too short' => ['abc123def456ghi789jkl012mno345p', 'invalid_format'],
            'random key too long' => ['abc123def456ghi789jkl012mno345pqr', 'invalid_format'],
            'random key with special chars' => ['abc123def456ghi789jkl012mno345p@', 'invalid_format'],
            'array value' => [['test@example.com'], 'invalid_type'],
            'object value' => [(object)['key' => 'test'], 'invalid_type'],
            'boolean value' => [true, 'invalid_type'],
            'numeric value' => [123456789, 'invalid_type'],
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
     * Test phone number formatting and edge cases
     */
    public function testPhoneNumberFormatting(): void
    {
        // Test that phone validation handles different formats
        $phoneVariations = [
            '11999999999',      // Standard mobile
            '+5511999999999',   // With country code
            '1133334444',       // Landline
            '11988887777',      // Different mobile pattern
        ];

        foreach ($phoneVariations as $phone) {
            $this->assertTrue(
                $this->rule->passes('pix.key', $phone),
                "Phone {$phone} should be valid"
            );
        }
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