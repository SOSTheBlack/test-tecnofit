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
     * Saques pendentes
     */
    public function pendingWithdraws(): HasMany
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id')
            ->where('status', AccountWithdraw::STATUS_PENDING)
            ->where('done', false);
    }

    /**
     * Saques completados
     */
    public function completedWithdraws(): HasMany
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id')
            ->where('status', AccountWithdraw::STATUS_COMPLETED)
            ->where('done', true);
    }

    /**
     * Saques agendados
     */
    public function scheduledWithdraws(): HasMany
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id')
            ->where('scheduled', true)
            ->whereNotNull('scheduled_for');
    }

    /**
     * Verifica se a conta tem saldo suficiente para um saque
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Verifica se a conta tem saldo suficiente considerando saques pendentes
     */
    public function hasSufficientAvailableBalance(float $amount): bool
    {
        $pendingAmount = $this->getTotalPendingWithdrawAmount();
        $availableBalance = $this->balance - $pendingAmount;
        return $availableBalance >= $amount;
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
        return (float) $this->pendingWithdraws()->sum('amount');
    }

    /**
     * Debita um valor da conta
     */
    public function debit(float $amount): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        return $this->update([
            'balance' => $this->balance - $amount,
        ]);
    }

    /**
     * Credita um valor na conta
     */
    public function credit(float $amount): bool
    {
        return $this->update([
            'balance' => $this->balance + $amount,
        ]);
    }

    /**
     * Cria um novo saque para esta conta
     */
    public function createWithdraw(array $withdrawData): AccountWithdraw
    {
        $withdrawData['account_id'] = $this->id;
        $withdrawData['transaction_id'] = AccountWithdraw::generateTransactionId();
        
        return AccountWithdraw::create($withdrawData);
    }

    /**
     * Verifica se a conta está ativa (não deletada)
     */
    public function isActive(): bool
    {
        return $this->deleted_at === null;
    }

    /**
     * Obtém histórico de saques (últimos 30 dias)
     */
    public function getRecentWithdrawHistory(int $days = 30)
    {
        return $this->withdraws()
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->with('pixData')
            ->get();
    }

    /**
     * Obtém estatísticas de saques
     */
    public function getWithdrawStats(): array
    {
        $totalWithdraws = $this->withdraws()->count();
        $totalAmount = (float) $this->withdraws()->sum('amount');
        $pendingCount = $this->withdraws()->where('status', AccountWithdraw::STATUS_PENDING)->count();
        $pendingAmount = (float) $this->withdraws()->where('status', AccountWithdraw::STATUS_PENDING)->sum('amount');
        $completedCount = $this->withdraws()->where('status', AccountWithdraw::STATUS_COMPLETED)->count();
        $completedAmount = (float) $this->withdraws()->where('status', AccountWithdraw::STATUS_COMPLETED)->sum('amount');
        $failedCount = $this->withdraws()->where('status', AccountWithdraw::STATUS_FAILED)->count();
        $failedAmount = (float) $this->withdraws()->where('status', AccountWithdraw::STATUS_FAILED)->sum('amount');
        
        return [
            'total_withdraws' => $totalWithdraws,
            'total_amount' => $totalAmount,
            'pending_count' => $pendingCount,
            'pending_amount' => $pendingAmount,
            'completed_count' => $completedCount,
            'completed_amount' => $completedAmount,
            'failed_count' => $failedCount,
            'failed_amount' => $failedAmount,
            'available_balance' => $this->getAvailableBalance(),
        ];
    }

    /**
     * Scope para contas com saldo mínimo
     */
    public function scopeWithMinimumBalance($query, float $minimumBalance)
    {
        return $query->where('balance', '>=', $minimumBalance);
    }

    /**
     * Scope para contas ativas
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
