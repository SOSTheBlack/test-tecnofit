<?php

declare(strict_types=1);

namespace App\Request\Validator;

use App\Enum\PixKeyTypeEnum;
use App\Enum\WithdrawMethodEnum;
use App\Service\Validator\PixKeyValidator;
use DateTime;
use Exception;

class WithdrawRequestValidator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): array
    {
        $this->validateMethod();
        $this->validatePix();
        $this->validateAmount();
        $this->validateSchedule();

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'data' => $this->data
        ];
    }

    public function validateWithBalance(float $currentBalance): array
    {
        $result = $this->validate();
        
        if ($result['valid']) {
            $this->validateAmountAgainstBalance($currentBalance);
            $result['valid'] = empty($this->errors);
            $result['errors'] = $this->errors;
        }

        return $result;
    }

    private function validateMethod(): void
    {
        if (!isset($this->data['method'])) {
            $this->addError('method', 'Campo method é obrigatório.');
            return;
        }

        if (!is_string($this->data['method'])) {
            $this->addError('method', 'Campo method deve ser uma string.');
            return;
        }

        if (!WithdrawMethodEnum::isValid($this->data['method'])) {
            $this->addError('method', sprintf(
                'Método de saque inválido. Métodos disponíveis: %s',
                implode(', ', WithdrawMethodEnum::getAvailableMethods())
            ));
        }
    }

    private function validatePix(): void
    {
        if (!isset($this->data['pix'])) {
            $this->addError('pix', 'Campo pix é obrigatório.');
            return;
        }

        if (!is_array($this->data['pix'])) {
            $this->addError('pix', 'Campo pix deve ser um objeto.');
            return;
        }

        // Valida se o método corresponde à chave PIX
        if (isset($this->data['method']) && $this->data['method'] !== 'PIX') {
            $this->addError('pix', 'Campo pix só deve ser informado quando method for PIX.');
            return;
        }

        $this->validatePixType();
        $this->validatePixKey();
    }

    private function validatePixType(): void
    {
        if (!isset($this->data['pix']['type'])) {
            $this->addError('pix.type', 'Campo pix.type é obrigatório.');
            return;
        }

        if (!is_string($this->data['pix']['type'])) {
            $this->addError('pix.type', 'Campo pix.type deve ser uma string.');
            return;
        }

        if (!PixKeyTypeEnum::isValid($this->data['pix']['type'])) {
            $this->addError('pix.type', sprintf(
                'Tipo de chave PIX inválido. Tipos disponíveis: %s',
                implode(', ', PixKeyTypeEnum::getAvailableTypes())
            ));
        }
    }

    private function validatePixKey(): void
    {
        if (!isset($this->data['pix']['key'])) {
            $this->addError('pix.key', 'Campo pix.key é obrigatório.');
            return;
        }

        if (!is_string($this->data['pix']['key'])) {
            $this->addError('pix.key', 'Campo pix.key deve ser uma string.');
            return;
        }

        if (empty(trim($this->data['pix']['key']))) {
            $this->addError('pix.key', 'Campo pix.key não pode estar vazio.');
            return;
        }

        // Valida o formato da chave baseado no tipo
        if (isset($this->data['pix']['type'])) {
            $validation = PixKeyValidator::validateKey(
                $this->data['pix']['type'],
                $this->data['pix']['key']
            );

            if (!$validation['valid']) {
                $this->addError('pix.key', $validation['message']);
            }
        }
    }

    private function validateAmount(): void
    {
        if (!isset($this->data['amount'])) {
            $this->addError('amount', 'Campo amount é obrigatório.');
            return;
        }

        if (!is_numeric($this->data['amount'])) {
            $this->addError('amount', 'Campo amount deve ser um número.');
            return;
        }

        $amount = (float) $this->data['amount'];

        if ($amount <= 0) {
            $this->addError('amount', 'Valor do saque deve ser maior que zero.');
            return;
        }

        if ($amount > 999999.99) {
            $this->addError('amount', 'Valor do saque não pode ser superior a R$ 999.999,99.');
        }

        // Verifica se tem mais de 2 casas decimais
        if (round($amount, 2) !== $amount) {
            $this->addError('amount', 'Valor do saque deve ter no máximo 2 casas decimais.');
        }
    }

    private function validateAmountAgainstBalance(float $currentBalance): void
    {
        if (!isset($this->data['amount'])) {
            return; // Já validado anteriormente
        }

        $amount = (float) $this->data['amount'];

        if ($amount > $currentBalance) {
            $this->addError('amount', sprintf(
                'Saldo insuficiente. Saldo atual: R$ %.2f, Valor solicitado: R$ %.2f',
                $currentBalance,
                $amount
            ));
        }
    }

    private function validateSchedule(): void
    {
        // Schedule é opcional, pode ser null
        if (!isset($this->data['schedule']) || $this->data['schedule'] === null) {
            return;
        }

        if (!is_string($this->data['schedule'])) {
            $this->addError('schedule', 'Campo schedule deve ser uma string ou null.');
            return;
        }

        try {
            $scheduleDate = new DateTime($this->data['schedule']);
            $now = new DateTime();
            $maxDate = new DateTime('+7 days');

            // Verifica se a data não é passada
            if ($scheduleDate <= $now) {
                $this->addError('schedule', 'Data de agendamento deve ser futura.');
                return;
            }

            // Verifica se não é mais de 7 dias à frente
            if ($scheduleDate > $maxDate) {
                $this->addError('schedule', 'Data de agendamento não pode ser superior a 7 dias à frente.');
                return;
            }

            // Valida formato da data
            if ($scheduleDate->format('Y-m-d H:i') !== $this->data['schedule']) {
                $this->addError('schedule', 'Formato de data inválido. Use: YYYY-MM-DD HH:MM');
            }

        } catch (Exception $e) {
            $this->addError('schedule', 'Data de agendamento inválida. Use o formato: YYYY-MM-DD HH:MM');
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
