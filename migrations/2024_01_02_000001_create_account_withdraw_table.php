<?php

declare(strict_types=1);

use App\Model\AccountWithdraw;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAccountWithdrawTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_withdraw', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('transaction_id')->nullable()->unique();

            $table->string('method');
            $table->decimal('amount', 10, 2);
            $table->boolean('scheduled')->default(false);
            $table->string('status')->default(AccountWithdraw::STATUS_NEW);
            $table->boolean('done')->default(false);
            $table->boolean('error')->default(false);
            $table->string('error_reason')->nullable();
            $table->json('meta')->nullable();

            $table->dateTime('scheduled_for')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_withdraw');
    }
}
