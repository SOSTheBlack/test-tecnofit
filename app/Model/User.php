<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $cpf
 * @property string $phone
 * @property Carbon $email_verified_at
 * @property string $password
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class User extends Model
{
    use SoftDeletes;

    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'cpf',
        'phone',
        'password',
        'email_verified_at',
    ];

    protected array $hidden = [
        'password',
    ];

    protected array $casts = [
        'id' => 'integer',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com saques PIX
     */
    public function pixWithdrawals()
    {
        return $this->hasMany(PixWithdrawal::class);
    }

    /**
     * Relacionamento com logs de auditoria
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Verificar se o usuário está verificado
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Verificar se o usuário pode realizar saques
     */
    public function canWithdraw(): bool
    {
        return $this->isVerified();
    }
}
