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
    public const STATUS_NEW = 'new';
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
     * Gera um novo transaction_id único
     */
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN_' . strtoupper(uniqid()) . '_' . time();
        } while (self::query()->where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

        /**
     * Scope para filtrar por status
     */
    public function scopeByStatus(\Hyperf\Database\Model\Builder $query, string $status): \Hyperf\Database\Model\Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar saques agendados
     */
    public function scopeScheduled(\Hyperf\Database\Model\Builder $query): mixed
    {
        return $query->whereNotNull('scheduled_for');
    }

    /**
     * Scope para filtrar saques imediatos
     */
    public function scopeImmediate(\Hyperf\Database\Model\Builder $query): mixed
    {
        return $query->whereNull('scheduled_for');
    }

    /**
     * Scope para filtrar por método
     */
    public function scopeByMethod(\Hyperf\Database\Model\Builder $query, string $method): \Hyperf\Database\Model\Builder
    {
        return $query->where('method', $method);
    }
}
