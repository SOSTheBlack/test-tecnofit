<?php

declare(strict_types=1);

namespace Test\Feature;

use App\Model\Account;
use HyperfTest\TestCase;

class WithdrawControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function initDatabase(): void
    {
        // Cria conta de teste
        Account::create([
            'id' => 'test-account-id',
            'name' => 'Test Account',
            'balance' => 1000.00
        ]);
    }

    private function cleanDatabase(): void
    {
        Account::where('id', 'test-account-id')->forceDelete();
    }

    public function testSuccessfulImmediateWithdrawWithEmail(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 150.75,
            'schedule' => null
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(200, $response['status']);
        $this->assertSame('success', $response['data']['status']);
        $this->assertSame('Saque processado com sucesso.', $response['data']['message']);
        $this->assertSame('test-account-id', $response['data']['data']['account_id']);
        $this->assertSame(150.75, $response['data']['data']['amount']);
        $this->assertSame('PIX', $response['data']['data']['method']);
        $this->assertSame(849.25, $response['data']['data']['new_balance']);

        // Verifica se o saldo foi atualizado no banco
        $account = Account::find('test-account-id');
        $this->assertSame(849.25, (float) $account->balance);
    }

    public function testSuccessfulWithdrawWithCpf(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'CPF',
                'key' => '11144477735'
            ],
            'amount' => 200.00
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(200, $response['status']);
        $this->assertSame('success', $response['data']['status']);
        $this->assertSame(200.00, $response['data']['data']['amount']);
        $this->assertSame(800.00, $response['data']['data']['new_balance']);
    }

    public function testSuccessfulScheduledWithdraw(): void
    {
        $scheduleDate = date('Y-m-d H:i', strtotime('+2 days'));
        
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'phone',
                'key' => '11999999999'
            ],
            'amount' => 100.00,
            'schedule' => $scheduleDate
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(201, $response['status']);
        $this->assertSame('success', $response['data']['status']);
        $this->assertSame('Saque agendado com sucesso.', $response['data']['message']);
        $this->assertSame(100.00, $response['data']['data']['amount']);
        $this->assertSame($scheduleDate, $response['data']['data']['scheduled_for']);
        $this->assertSame(1000.00, $response['data']['data']['current_balance']); // Saldo não foi alterado ainda

        // Verifica se o saldo não foi alterado (agendamento)
        $account = Account::find('test-account-id');
        $this->assertSame(1000.00, (float) $account->balance);
    }

    public function testAccountNotFound(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00
        ];

        $response = $this->json('POST', '/account/non-existent-id/balance/withdraw', $data);

        $this->assertSame(404, $response['status']);
        $this->assertSame('error', $response['data']['status']);
        $this->assertSame('Conta não encontrada.', $response['data']['message']);
        $this->assertArrayHasKey('accountId', $response['data']['errors']);
    }

    public function testInsufficientBalance(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 2000.00 // Maior que o saldo de 1000.00
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(422, $response['status']);
        $this->assertSame('error', $response['data']['status']);
        $this->assertSame('Dados da requisição inválidos.', $response['data']['message']);
        $this->assertArrayHasKey('amount', $response['data']['errors']);
        $this->assertStringContainsString('Saldo insuficiente', $response['data']['errors']['amount'][0]);
    }

    public function testInvalidEmail(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'invalid-email'
            ],
            'amount' => 100.00
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(422, $response['status']);
        $this->assertSame('error', $response['data']['status']);
        $this->assertArrayHasKey('pix.key', $response['data']['errors']);
        $this->assertStringContainsString('Formato de e-mail inválido', $response['data']['errors']['pix.key'][0]);
    }

    public function testInvalidCpf(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'CPF',
                'key' => '12345678901' // CPF inválido
            ],
            'amount' => 100.00
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(422, $response['status']);
        $this->assertArrayHasKey('pix.key', $response['data']['errors']);
        $this->assertStringContainsString('CPF inválido', $response['data']['errors']['pix.key'][0]);
    }

    public function testInvalidPhone(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'phone',
                'key' => '123'
            ],
            'amount' => 100.00
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(422, $response['status']);
        $this->assertArrayHasKey('pix.key', $response['data']['errors']);
        $this->assertStringContainsString('Formato de telefone inválido', $response['data']['errors']['pix.key'][0]);
    }

    public function testNegativeAmount(): void
    {
        $data = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => -50.00
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(422, $response['status']);
        $this->assertArrayHasKey('amount', $response['data']['errors']);
        $this->assertStringContainsString('Valor do saque deve ser maior que zero', $response['data']['errors']['amount'][0]);
    }

    public function testInvalidMethod(): void
    {
        $data = [
            'method' => 'INVALID_METHOD',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 100.00
        ];

        $response = $this->json('POST', '/account/test-account-id/balance/withdraw', $data);

        $this->assertSame(422, $response['status']);
        $this->assertArrayHasKey('method', $response['data']['errors']);
        $this->assertStringContainsString('Método de saque inválido', $response['data']['errors']['method'][0]);
    }
}
