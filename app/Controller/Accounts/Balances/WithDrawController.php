<?php

declare(strict_types=1);

namespace App\Controller\Accounts\Balances;

use App\Repository\Contract\AccountRepositoryInterface;
use App\Request\WithdrawRequest;
use App\Service\AccountService;
use App\UseCase\Account\Withdraw;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Throwable;

class WithDrawController extends BalanceController
{
    private string $account;
    private array $data;

    public function __construct(
        private ResponseInterface $response,
        private AccountRepositoryInterface $accountRepository,
        private AccountService $accountService,
    ) {
    }

    public function __invoke(string $accountId, WithdrawRequest $request): PsrResponseInterface
    {
        $this->defineData($request);
        $this->defineAccount($accountId);

        // $transaction = new Withdraw

        return $this->processWithdraw();

        // Se for agendamento
        if ($data['schedule'] ?? null) {
            return $this->scheduleWithdraw($account, $data);
        }

        // Saque imediato
        return $this->processImmediateWithdraw($account, $data);
    }

    private function defineData($request): void
    {
        $this->data = $request->validated();
    }

    private function defineAccount(string $accountId): void
    {
        $this->account = $accountId;
    }

    private function processWithdraw(): PsrResponseInterface
    {
        if ($data['schedule'] ?? null !== null) {
            return $this->scheduleWithdraw($account, $data);
        }

        return $this->processImmediateWithdraw($account, $data);
    }

    private function processImmediateWithdraw(
        $account,
        array $data
    ): PsrResponseInterface {
        $amount = (float) $data['amount'];

        // Processa o débito usando o Service
        $withdrawResult = $this->accountService->processWithdraw($account->id, $amount);

        if (!$withdrawResult['success']) {
            return $this->response->json([
                'status' => 'error',
                'message' => $withdrawResult['message'],
                'errors' => ['amount' => [$withdrawResult['message']]]
            ])->withStatus(500);
        }

        // Aqui você implementaria a integração com o provedor PIX
        // $pixService->processWithdraw($data['pix'], $amount);

        return $this->response->json([
            'status' => 'success',
            'message' => 'Saque processado com sucesso.',
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
        $account,
        array $data
    ): PsrResponseInterface {
        $amount = (float) $data['amount'];
        $schedule = $data['schedule'];

        // Aqui você implementaria a lógica de agendamento
        // Criar registro na tabela de saques agendados
        // Configurar job para processar na data agendada

        return $this->response->json([
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
