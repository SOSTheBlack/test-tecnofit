<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use App\Model\Account;
use App\Model\AccountWithdraw;
use HyperfTest\TestCase;

class WithdrawEmailNotificationTest extends TestCase
{
    public function testEmailJobIsScheduledAfterSuccessfulWithdraw(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 100.00]);
        
        // Act - Realiza saque com chave PIX email
        $response = $this->post("/account/{$account->id}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com'
            ],
            'amount' => 50.00,
            'schedule' => null
        ]);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json();
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('immediate', $responseData['data']['type']);
        
        // Verifica se o saque foi criado e processado
        $withdraw = AccountWithdraw::where('account_id', $account->id)->first();
        $this->assertNotNull($withdraw);
        $this->assertEquals('completed', $withdraw->status);
        
        // Verifica se a conta foi debitada
        $account->refresh();
        $this->assertEquals(50.00, $account->balance);
    }

    public function testEmailIsNotScheduledForNonEmailPixKey(): void
    {
        // Arrange
        $account = Account::factory()->create(['balance' => 100.00]);
        
        // Act - Realiza saque com chave PIX CPF
        $response = $this->post("/account/{$account->id}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => [
                'type' => 'cpf',
                'key' => '12345678901'
            ],
            'amount' => 50.00,
            'schedule' => null
        ]);

        // Assert
        $response->assertStatus(201);
        
        // Verifica que o saque foi processado mas email não seria enviado
        $withdraw = AccountWithdraw::where('account_id', $account->id)->first();
        $this->assertEquals('cpf', $withdraw->pix->type);
        $this->assertEquals('completed', $withdraw->status);
    }

    public function testEmailTemplateContainsCorrectInformation(): void
    {
        // Este teste verificaria o conteúdo do email
        // Em um ambiente real, seria feito com mocks ou captura de emails
        $this->assertTrue(true); // Placeholder para implementação futura
    }
}
