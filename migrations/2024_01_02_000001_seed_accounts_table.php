<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class SeedAccountsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insere algumas contas de teste
        $now = date('Y-m-d H:i:s');
        $accounts = [
            [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'name' => 'Conta Teste 1',
                'balance' => 1000.50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => '223e4567-e89b-12d3-a456-426614174001',
                'name' => 'Conta Teste 2',
                'balance' => 500.25,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => '323e4567-e89b-12d3-a456-426614174002',
                'name' => 'Conta Teste 3',
                'balance' => 0.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($accounts as $account) {
            Db::table('accounts')->insert($account);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Db::table('accounts')->whereIn('id', [
            '123e4567-e89b-12d3-a456-426614174000',
            '223e4567-e89b-12d3-a456-426614174001',
            '323e4567-e89b-12d3-a456-426614174002',
        ])->delete();
    }
}
