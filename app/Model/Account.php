<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property string $id
 * @property string $name
 * @property float $balance
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property AccountWithdraw[] $withdraws
 */
class Account extends Model
{
    use SoftDeletes;

    protected ?string $table = 'account';

    public bool $incrementing = false;
    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'name',
        'balance',
    ];

    protected array $casts = [
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com saques
     */
    public function withdraws(): HasMany
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id');
    }

    /**
     * Conta de saques pendentes
     */
    public function getPendingWithdrawsCount(): int
    {
        return $this->withdraws()
            ->whereIn('status', [AccountWithdraw::STATUS_PENDING, AccountWithdraw::STATUS_SCHEDULED])
            ->where('done', false)
            ->count();
    }

    /**
     * Obtém o saldo disponível (saldo atual - saques pendentes)
     */
    public function getAvailableBalance(): float
    {
        $pendingAmount = $this->getTotalPendingWithdrawAmount();

        return max(0, $this->balance - $pendingAmount);
    }

    /**
     * Obtém o total de saques pendentes
     */
    public function getTotalPendingWithdrawAmount(): float
    {
        return (float) $this->withdraws()
            ->whereIn('status', [AccountWithdraw::STATUS_PENDING, AccountWithdraw::STATUS_SCHEDULED])
            ->where('done', false)
            ->sum('amount');
    }


}
