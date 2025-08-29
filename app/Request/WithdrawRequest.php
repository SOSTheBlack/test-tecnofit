<?php

declare(strict_types=1);

namespace App\Request;

use App\Rules\PixKeyRule;
use App\Rules\PixTypeRule;
use App\Rules\ScheduleRule;
use App\Rules\WithdrawMethodRule;
use Hyperf\Validation\Request\FormRequest;

class WithdrawRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', new WithdrawMethodRule()],
            'pix' => 'required|array',
            'pix.type' => ['required', 'string', new PixTypeRule()],
            'pix.key' => ['required', 'string', new PixKeyRule()],
            'amount' => 'required|numeric|min:0.01',
            'schedule' => ['nullable', new ScheduleRule()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'method.required' => 'Método de saque é obrigatório.',
            'method.string' => 'Método de saque deve ser uma string.',
            'pix.required' => 'Dados do PIX são obrigatórios.',
            'pix.array' => 'Dados do PIX devem ser um objeto.',
            'pix.type.required' => 'Tipo da chave PIX é obrigatório.',
            'pix.type.string' => 'Tipo da chave PIX deve ser uma string.',
            'pix.key.required' => 'Chave PIX é obrigatória.',
            'pix.key.string' => 'Chave PIX deve ser uma string.',
            'amount.required' => 'Valor do saque é obrigatório.',
            'amount.numeric' => 'Valor do saque deve ser numérico.',
            'amount.min' => 'Valor do saque deve ser maior que zero.',
        ];
    }

    /**
     * Validar saldo suficiente (será chamado depois que soubermos o saldo da conta)
     */
    public function validateBalance(float $accountBalance): array
    {
        $amount = (float) $this->input('amount');
        
        if ($amount > $accountBalance) {
            return [
                'valid' => false,
                'errors' => [
                    'amount' => [sprintf(
                        'Saldo insuficiente. Saldo disponível: R$ %.2f, Valor solicitado: R$ %.2f',
                        $accountBalance,
                        $amount
                    )]
                ]
            ];
        }

        return ['valid' => true, 'errors' => []];
    }
}
