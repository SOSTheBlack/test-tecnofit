<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\PixKeyTypeEnum;
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

    /**
     * Verifica se a chave PIX é do tipo CPF
     */
    public function isCpfKey(): bool
    {
        return $this->type === PixKeyTypeEnum::CPF;
    }

    /**
     * Verifica se a chave PIX é do tipo CNPJ
     */
    public function isCnpjKey(): bool
    {
        return $this->type === PixKeyTypeEnum::CNPJ;
    }

    /**
     * Verifica se a chave PIX é do tipo Email
     */
    public function isEmailKey(): bool
    {
        return $this->type === PixKeyTypeEnum::EMAIL;
    }

    /**
     * Verifica se a chave PIX é do tipo Telefone
     */
    public function isPhoneKey(): bool
    {
        return $this->type === PixKeyTypeEnum::PHONE;
    }

    /**
     * Verifica se a chave PIX é do tipo Aleatória
     */
    public function isRandomKey(): bool
    {
        return $this->type === PixKeyTypeEnum::RANDOM_KEY;
    }

    /**
     * Formata a chave PIX para exibição
     */
    public function getFormattedKey(): string
    {
        switch ($this->type) {
            case PixKeyTypeEnum::CPF:
                return $this->formatCpf($this->key);
            case PixKeyTypeEnum::CNPJ:
                return $this->formatCnpj($this->key);
            case PixKeyTypeEnum::PHONE:
                return $this->formatPhone($this->key);
            case PixKeyTypeEnum::EMAIL:
            case PixKeyTypeEnum::RANDOM_KEY:
            default:
                return $this->key;
        }
    }

    /**
     * Formata CPF
     */
    private function formatCpf(string $cpf): string
    {
        $cleanCpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cleanCpf) === 11) {
            return substr($cleanCpf, 0, 3) . '.' . 
                   substr($cleanCpf, 3, 3) . '.' . 
                   substr($cleanCpf, 6, 3) . '-' . 
                   substr($cleanCpf, 9, 2);
        }
        return $cpf;
    }

    /**
     * Formata CNPJ
     */
    private function formatCnpj(string $cnpj): string
    {
        $cleanCnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cleanCnpj) === 14) {
            return substr($cleanCnpj, 0, 2) . '.' . 
                   substr($cleanCnpj, 2, 3) . '.' . 
                   substr($cleanCnpj, 5, 3) . '/' . 
                   substr($cleanCnpj, 8, 4) . '-' . 
                   substr($cleanCnpj, 12, 2);
        }
        return $cnpj;
    }

    /**
     * Formata telefone
     */
    private function formatPhone(string $phone): string
    {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        if (strlen($cleanPhone) === 11) {
            return '(' . substr($cleanPhone, 0, 2) . ') ' . 
                   substr($cleanPhone, 2, 5) . '-' . 
                   substr($cleanPhone, 7, 4);
        } elseif (strlen($cleanPhone) === 10) {
            return '(' . substr($cleanPhone, 0, 2) . ') ' . 
                   substr($cleanPhone, 2, 4) . '-' . 
                   substr($cleanPhone, 6, 4);
        }
        return $phone;
    }

    /**
     * Valida se a chave PIX está no formato correto para o tipo
     */
    public function isValidKeyFormat(): bool
    {
        switch ($this->type) {
            case PixKeyTypeEnum::CPF:
                return $this->isValidCpf($this->key);
            case PixKeyTypeEnum::CNPJ:
                return $this->isValidCnpj($this->key);
            case PixKeyTypeEnum::EMAIL:
                return filter_var($this->key, FILTER_VALIDATE_EMAIL) !== false;
            case PixKeyTypeEnum::PHONE:
                return $this->isValidPhone($this->key);
            case PixKeyTypeEnum::RANDOM_KEY:
                return $this->isValidRandomKey($this->key);
            default:
                return false;
        }
    }

    /**
     * Valida CPF
     */
    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida CNPJ
     */
    private function isValidCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

        if ($result != $digits[0]) {
            return false;
        }

        $length++;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

        return $result == $digits[1];
    }

    /**
     * Valida telefone
     */
    private function isValidPhone(string $phone): bool
    {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        return strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 11;
    }

    /**
     * Valida chave aleatória (formato UUID)
     */
    private function isValidRandomKey(string $key): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key) === 1;
    }

    /**
     * Obtém a chave PIX mascarada para logs/auditoria
     */
    public function getMaskedKey(): string
    {
        switch ($this->type) {
            case PixKeyTypeEnum::CPF:
            case PixKeyTypeEnum::CNPJ:
                return substr($this->key, 0, 3) . str_repeat('*', strlen($this->key) - 6) . substr($this->key, -3);
            case PixKeyTypeEnum::EMAIL:
                $parts = explode('@', $this->key);
                if (count($parts) === 2) {
                    $name = substr($parts[0], 0, 2) . str_repeat('*', strlen($parts[0]) - 2);
                    return $name . '@' . $parts[1];
                }
                return $this->key;
            case PixKeyTypeEnum::PHONE:
                $cleanPhone = preg_replace('/\D/', '', $this->key);
                return substr($cleanPhone, 0, 2) . str_repeat('*', strlen($cleanPhone) - 4) . substr($cleanPhone, -2);
            case PixKeyTypeEnum::RANDOM_KEY:
            default:
                return substr($this->key, 0, 8) . str_repeat('*', strlen($this->key) - 16) . substr($this->key, -8);
        }
    }

    /**
     * Scope para filtrar por tipo de chave
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para chaves com external_id
     */
    public function scopeWithExternalId($query)
    {
        return $query->whereNotNull('external_id');
    }
}
