<?php

declare(strict_types=1);

namespace App\Controller\Accounts\Balances;

use App\Request\Validator\WithdrawRequestValidator;
use App\Service\AccountService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class WithDrawController extends BalanceController
{
    public function __construct(
        private AccountService $accountService
    ) {}

    public function __invoke(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        try {
            $data = $request->all();
            $accountId = $request->route('accountId');

            // Validação básica do accountId
            if (empty($accountId)) {
                return $response->json([
                    'status' => 'error',
                    'message' => 'ID da conta é obrigatório.',
                    'errors' => ['accountId' => ['ID da conta não pode estar vazio.']]
                ])->withStatus(400);
            }

            // Verifica se a conta existe usando o Service
            $account = $this->accountService->findAccountById($accountId);
            if (!$account) {
                return $response->json([
                    'status' => 'error',
                    'message' => 'Conta não encontrada.',
                    'errors' => ['accountId' => ['Conta com ID informado não existe.']]
                ])->withStatus(404);
            }

            // Validação dos dados da requisição
            $validator = new WithdrawRequestValidator($data);
            $validationResult = $validator->validateWithBalance((float) $account->balance);

            if (!$validationResult['valid']) {
                return $response->json([
                    'status' => 'error',
                    'message' => 'Dados da requisição inválidos.',
                    'errors' => $validationResult['errors']
                ])->withStatus(422);
            }

            // Processa o saque
            return $this->processWithdraw($request, $response, $account, $data);

        } catch (\Exception $e) {
            return $response->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor.',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ])->withStatus(500);
        }
    }

    private function processWithdraw(
        RequestInterface $request,
        ResponseInterface $response,
        $account,
        array $data
    ): PsrResponseInterface {
        $amount = (float) $data['amount'];
        $schedule = $data['schedule'] ?? null;

        // Se for agendamento
        if ($schedule !== null) {
            return $this->scheduleWithdraw($response, $account, $data);
        }

        // Saque imediato
        return $this->processImmediateWithdraw($response, $account, $data);
    }

    private function processImmediateWithdraw(
        ResponseInterface $response,
        $account,
        array $data
    ): PsrResponseInterface {
        $amount = (float) $data['amount'];

        // Processa o débito usando o Service
        $withdrawResult = $this->accountService->processWithdraw($account->id, $amount);

        if (!$withdrawResult['success']) {
            return $response->json([
                'status' => 'error',
                'message' => $withdrawResult['message'],
                'errors' => ['amount' => [$withdrawResult['message']]]
            ])->withStatus(500);
        }

        // Aqui você implementaria a integração com o provedor PIX
        // $pixService->processWithdraw($data['pix'], $amount);

        return $response->json([
            'status' => 'success',
            'message' => 'Saque processado com sucesso! 🚀 AUTO-RELOAD FUNCIONANDO!',
            'data' => [
                'account_id' => $account->id,
                'amount' => $amount,
                'method' => $data['method'],
                'pix' => $data['pix'],
                'new_balance' => $withdrawResult['data']['new_balance'],
                'processed_at' => $withdrawResult['data']['processed_at'],
                'transaction_id' => $this->generateTransactionId()
            ]
        ])->withStatus(200);
    }

    private function scheduleWithdraw(
        ResponseInterface $response,
        $account,
        array $data
    ): PsrResponseInterface {
        $amount = (float) $data['amount'];
        $schedule = $data['schedule'];

        // Aqui você implementaria a lógica de agendamento
        // Criar registro na tabela de saques agendados
        // Configurar job para processar na data agendada

        return $response->json([
            'status' => 'success',
            'message' => 'Saque agendado com sucesso.',
            'data' => [
                'account_id' => $account->id,
                'amount' => $amount,
                'method' => $data['method'],
                'pix' => $data['pix'],
                'scheduled_for' => $schedule,
                'current_balance' => (float) $account->balance,
                'scheduled_at' => date('Y-m-d H:i:s'),
                'transaction_id' => $this->generateTransactionId()
            ]
        ])->withStatus(201);
    }

    private function generateTransactionId(): string
    {
        return 'TXN_' . strtoupper(uniqid()) . '_' . time();
    }
}