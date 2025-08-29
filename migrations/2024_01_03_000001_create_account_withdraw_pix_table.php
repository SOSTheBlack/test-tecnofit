<?php

declare(strict_types=1);

use App\Enum\PixKeyTypeEnum;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAccountWithdrawPixTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_withdraw_pix', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_withdraw_id')->index();
            $table->string('external_id')->nullable()->comment('External reference ID(API)');
            $table->enum('type', PixKeyTypeEnum::getValues())->comment('Type of PIX key (CPF, CNPJ, email, phone, random)');
            $table->string('key');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_withdraw_id')->references('id')->on('account_withdraw');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_withdraw_pix');
    }
}
