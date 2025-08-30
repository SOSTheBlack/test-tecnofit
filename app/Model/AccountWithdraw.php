<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\WithdrawMethodEnum;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Stringable\Str;

/**
 * @property string $id
 * @property string $account_id
 * @property string $transaction_id
 * @property string $method
 * @property float $amount
 * @property bool $scheduled
 * @property string $status
 * @property bool $done
 * @property bool $error
 * @property string $error_reason
 * @property array $meta
 * @property Carbon $scheduled_for
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Account $account
 * @property AccountWithdrawPix $pixData
 */
class AccountWithdraw extends Model
{
    use SoftDeletes;

    protected ?string $table = 'account_withdraw';
    
    public bool $incrementing = false;
    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'account_id',
        'transaction_id',
        'method',
        'amount',
        'scheduled',
        'status',
        'done',
        'error',
        'error_reason',
        'meta',
        'scheduled_for',
    ];

    protected array $casts = [
        'amount' => 'decimal:2',
        'scheduled' => 'boolean',
        'done' => 'boolean',
        'error' => 'boolean',
        'meta' => 'array',
        'scheduled_for' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SCHEDULED = 'scheduled';

    /**
     * Relacionamento com Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Relacionamento com dados PIX
     */
    public function pixData(): HasOne
    {
        return $this->hasOne(AccountWithdrawPix::class, 'account_withdraw_id', 'id');
    }

    /**
     * Verifica se o saque está pendente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica se o saque está em processamento
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Verifica se o saque foi completado
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->done;
    }

    /**
     * Verifica se o saque falhou
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED || $this->error;
    }

    /**
     * Verifica se é um saque agendado
     */
    public function isScheduled(): bool
    {
        return $this->scheduled && $this->scheduled_for !== null;
    }

    /**
     * Verifica se o agendamento está pronto para execução
     */
    public function isReadyForExecution(): bool
    {
        return $this->isScheduled() 
            && $this->scheduled_for <= Carbon::now()
            && $this->isPending();
    }

    /**
     * Marca o saque como processando
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Marca o saque como completado
     */
    public function markAsCompleted(array $meta = []): bool
    {
        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'done' => true,
            'error' => false,
            'error_reason' => null,
        ];

        if (!empty($meta)) {
            $updateData['meta'] = array_merge($this->meta ?? [], $meta);
        }

        return $this->update($updateData);
    }

    /**
     * Marca o saque como falhou
     */
    public function markAsFailed(string $errorReason, array $meta = []): bool
    {
        $updateData = [
            'status' => self::STATUS_FAILED,
            'error' => true,
            'error_reason' => $errorReason,
            'done' => false,
        ];

        if (!empty($meta)) {
            $updateData['meta'] = array_merge($this->meta ?? [], $meta);
        }

        return $this->update($updateData);
    }

    /**
     * Cancela o saque
     */
    public function cancel(string $reason = 'Cancelled by user'): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'error_reason' => $reason,
        ]);
    }

    /**
     * Obtém saques pendentes para uma conta
     */
    public static function getPendingWithdrawsForAccount(string $accountId)
    {
        return static::where('account_id', $accountId)
            ->where('status', self::STATUS_PENDING)
            ->where('done', false)
            ->get();
    }

    /**
     * Obtém saques agendados prontos para execução
     */
    public static function getScheduledWithdrawsReadyForExecution()
    {
        return static::where('scheduled', true)
            ->where('status', self::STATUS_PENDING)
            ->where('scheduled_for', '<=', Carbon::now())
            ->with(['account', 'pixData'])
            ->get();
    }

    /**
     * Calcula o total de saques pendentes para uma conta
     */
    public static function getTotalPendingAmountForAccount(string $accountId): float
    {
        return (float) static::where('account_id', $accountId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING])
            ->sum('amount');
    }

    /**
     * Verifica se é um método PIX
     */
    public function isPixMethod(): bool
    {
        return $this->method === WithdrawMethodEnum::PIX;
    }

    /**
     * Gera um novo transaction_id único
     */
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN_' . strtoupper(uniqid()) . '_' . time();
        } while (static::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para saques agendados
     */
    public function scopeScheduled($query)
    {
        return $query->where('scheduled', true);
    }

    /**
     * Scope para saques imediatos
     */
    public function scopeImmediate($query)
    {
        return $query->where('scheduled', false);
    }

    /**
     * Scope para saques por método
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }
}
