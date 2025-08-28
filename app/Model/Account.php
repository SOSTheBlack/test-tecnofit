<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property float $balance
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Account extends Model
{
    use SoftDeletes;

    protected ?string $table = 'accounts';
    
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
}
