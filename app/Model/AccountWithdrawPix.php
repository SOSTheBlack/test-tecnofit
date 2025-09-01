<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property string $id
 * @property string $account_withdraw_id
 * @property string $external_id
 * @property string $type
 * @property string $key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property AccountWithdraw $withdraw
 */
class AccountWithdrawPix extends Model
{
    use SoftDeletes;

    protected ?string $table = 'account_withdraw_pix';

    public bool $incrementing = false;
    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'account_withdraw_id',
        'external_id',
        'type',
        'key',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com AccountWithdraw
     */
    public function withdraw(): BelongsTo
    {
        return $this->belongsTo(AccountWithdraw::class, 'account_withdraw_id', 'id');
    }
}
