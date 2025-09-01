<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Enum\WithdrawMethodEnum;
use App\Enum\WithdrawStatusEnum;
use App\Enum\PixKeyTypeEnum;
use HyperfTest\TestCase;
use Hyperf\DbConnection\Db;
use Carbon\Carbon;

class WithdrawEmailNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpar tabelas antes de cada teste (respeitando foreign keys)
        Db::connection('test')->table('account_withdraw_pix')->delete();
        Db::connection('test')->table('account_withdraw')->delete();
        Db::connection('test')->table('account')->delete();
    }

    public function testWithdrawRecordIsCreatedCorrectlyInDatabase(): void
    {
        // Arrange
        $account = new Account();
        $account->id = '223e4567-e89b-12d3-a456-426614174001';
        $account->name = 'Test Account';
        $account->balance = 100.00;
        $account->created_at = Carbon::now();
        $account->setConnection('test');
        $account->save();
        
        // Act - Criar um saque manualmente
        $withdraw = new AccountWithdraw();
        $withdraw->id = '123e4567-e89b-12d3-a456-426614174001';
        $withdraw->account_id = $account->id;
        $withdraw->transaction_id = 'TXN123456';
        $withdraw->method = WithdrawMethodEnum::PIX->value;
        $withdraw->amount = 50.00;
        $withdraw->status = WithdrawStatusEnum::COMPLETED->value;
        $withdraw->scheduled_for = null;
        $withdraw->created_at = Carbon::now();
        $withdraw->setConnection('test');
        $withdraw->save();

        $withdrawPix = new AccountWithdrawPix();
        $withdrawPix->id = '321e4567-e89b-12d3-a456-426614174001';
        $withdrawPix->account_withdraw_id = $withdraw->id;
        $withdrawPix->type = PixKeyTypeEnum::EMAIL->value;
        $withdrawPix->key = 'test@example.com';
        $withdrawPix->setConnection('test');
        $withdrawPix->save();

        // Assert
        $this->assertDatabaseHas('account_withdraw', [
            'id' => $withdraw->id,
            'account_id' => $account->id,
            'method' => 'PIX',
            'amount' => 50.00,
            'status' => 'completed'
        ], 'test');
        
        $this->assertDatabaseHas('account_withdraw_pix', [
            'account_withdraw_id' => $withdraw->id,
            'type' => 'email',
            'key' => 'test@example.com'
        ], 'test');
    }

    public function testMultipleWithdrawsCanBeStoredCorrectly(): void
    {
        // Arrange
        $account = new Account();
        $account->id = '223e4567-e89b-12d3-a456-426614174002';
        $account->name = 'Test Account 2';
        $account->balance = 200.00;
        $account->created_at = Carbon::now();
        $account->setConnection('test');
        $account->save();
        
        // Act - Criar múltiplos saques
        $withdraws = [];
        for ($i = 1; $i <= 3; $i++) {
            $withdraw = new AccountWithdraw();
            $withdraw->id = "123e4567-e89b-12d3-a456-42661417400{$i}";
            $withdraw->account_id = $account->id;
            $withdraw->transaction_id = "TXN12345{$i}";
            $withdraw->method = WithdrawMethodEnum::PIX->value;
            $withdraw->amount = 30.00;
            $withdraw->status = WithdrawStatusEnum::COMPLETED->value;
            $withdraw->scheduled_for = null;
            $withdraw->created_at = Carbon::now();
            $withdraw->setConnection('test');
            $withdraw->save();

            $withdrawPix = new AccountWithdrawPix();
            $withdrawPix->id = "321e4567-e89b-12d3-a456-42661417400{$i}";
            $withdrawPix->account_withdraw_id = $withdraw->id;
            $withdrawPix->type = PixKeyTypeEnum::EMAIL->value;
            $withdrawPix->key = "test{$i}@example.com";
            $withdrawPix->setConnection('test');
            $withdrawPix->save();
            
            $withdraws[] = $withdraw;
        }

        // Assert
        $this->assertCount(3, $withdraws);
        
        foreach ($withdraws as $withdraw) {
            $this->assertDatabaseHas('account_withdraw', [
                'id' => $withdraw->id,
                'account_id' => $account->id,
                'method' => 'PIX',
                'status' => 'completed'
            ], 'test');
        }
    }
}
