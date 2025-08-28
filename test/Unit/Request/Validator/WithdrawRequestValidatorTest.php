<?php

declare(strict_types=1);

namespace Test\Unit\Request\Validator;

use App\Request\Validator\WithdrawRequestValidator;
use PHPUnit\Framework\TestCase;

class WithdrawRequestValidatorTest extends TestCase
{
    public function testValidRequestWithEmailKey(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.50
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidRequestWithCpfKey(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'CPF',
                'key' => '11144477735'
            ],
            'amount' => 250.75
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidRequestWithPhoneKey(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'phone',
                'key' => '11999999999'
            ],
            'amount' => 50.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidRequestWithSchedule(): void
    {
        $futureDate = date('Y-m-d H:i', strtotime('+2 days'));
        
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00,
            'schedule' => $futureDate
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testInvalidMethodError(): void
    {
        $data = [
            'method' => 'INVALID_METHOD',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('method', $result['errors']);
    }

    public function testMissingMethodError(): void
    {
        $data = [
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('method', $result['errors']);
    }

    public function testInvalidEmailError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'invalid-email'
            ],
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('pix.key', $result['errors']);
    }

    public function testInvalidCpfError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'CPF',
                'key' => '12345678901' // CPF inválido
            ],
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('pix.key', $result['errors']);
    }

    public function testInvalidPhoneError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'phone',
                'key' => '123' // Telefone inválido
            ],
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('pix.key', $result['errors']);
    }

    public function testNegativeAmountError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => -50.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('amount', $result['errors']);
    }

    public function testZeroAmountError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 0
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('amount', $result['errors']);
    }

    public function testMissingAmountError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ]
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('amount', $result['errors']);
    }

    public function testInsufficientBalanceError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 1000.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validateWithBalance(500.00); // Saldo menor que o valor

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('amount', $result['errors']);
    }

    public function testPastScheduleError(): void
    {
        $pastDate = date('Y-m-d H:i', strtotime('-1 day'));
        
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00,
            'schedule' => $pastDate
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('schedule', $result['errors']);
    }

    public function testFarFutureScheduleError(): void
    {
        $farFutureDate = date('Y-m-d H:i', strtotime('+10 days')); // Mais de 7 dias
        
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00,
            'schedule' => $farFutureDate
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('schedule', $result['errors']);
    }

    public function testInvalidScheduleFormatError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00,
            'schedule' => 'invalid-date'
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('schedule', $result['errors']);
    }

    public function testMissingPixDataError(): void
    {
        $data = [
            'method' => 'PIX',
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('pix', $result['errors']);
    }

    public function testMissingPixTypeError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'key' => 'test@example.com'
            ],
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('pix.type', $result['errors']);
    }

    public function testMissingPixKeyError(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email'
            ],
            'amount' => 100.00
        ];

        $validator = new WithdrawRequestValidator($data);
        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('pix.key', $result['errors']);
    }
}
