<?php

declare(strict_types=1);

namespace App\Service\Email;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\DataTransfer\Account\Balance\AccountWithdrawPixData;
use App\Model\AccountWithdraw;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

/**
 * Serviço de email para notificações do sistema
 *
 * Centraliza o envio de emails relacionados a saques e outras operações.
 * Migrado para trabalhar com DTOs em vez de models diretamente.
 */
class EmailService
{
    private LoggerInterface $logger;
    private Mailer $mailer;
    private string $appName;
    private string $fromAddress;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('email', 'default');

        // Configuração do Mailhog para desenvolvimento
        $transport = new EsmtpTransport('mailhog', 1025);

        $this->mailer = new Mailer($transport);

        // Configurações obtidas diretamente do env() ou valores padrão
        $this->appName = env('APP_NAME', 'TecnofitPixAPI');
        $this->fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@tecnofit.com');
    }

    /**
     * Envia confirmação de saque usando DTOs
     *
     * @param AccountWithdrawData $withdrawData DTO do saque
     * @param AccountData $accountData DTO da conta
     * @param AccountWithdrawPixData $pixData DTO dos dados PIX
     * @return bool Verdadeiro se enviado com sucesso
     * @throws \Throwable Quando o envio falha
     */
    public function sendWithdrawConfirmationFromDTOs(
        AccountWithdrawData $withdrawData,
        AccountData $accountData,
        AccountWithdrawPixData $pixData,
    ): bool {
        try {
            if ($pixData->type->value !== 'email') {
                $this->logger->warning("Tentativa de envio de email para chave PIX não-email", [
                    'withdraw_id' => $withdrawData->id,
                    'pix_type' => $pixData->type->value,
                ]);

                return false;
            }

            $email = (new Email())
                ->from($this->fromAddress)
                ->to($pixData->key)
                ->subject("Confirmação de Saque PIX - {$this->appName}")
                ->html($this->buildEmailTemplateFromDTOs($withdrawData, $accountData, $pixData));

            $this->mailer->send($email);

            $this->logger->info("Email de confirmação de saque enviado", [
                'withdraw_id' => $withdrawData->id,
                'account_id' => $accountData->id,
                'recipient' => $pixData->key,
                'amount' => $withdrawData->amount,
                'sent' => true,
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->logger->error("Falha ao enviar email de confirmação", [
                'withdraw_id' => $withdrawData->id,
                'account_id' => $accountData->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Envia notificação genérica de saque (para métodos não-PIX)
     *
     * @param AccountWithdrawData $withdrawData DTO do saque
     * @param AccountData $accountData DTO da conta
     * @return bool Verdadeiro se enviado com sucesso
     */
    public function sendGenericWithdrawConfirmation(
        AccountWithdrawData $withdrawData,
        AccountData $accountData,
    ): bool {
        try {
            // Para métodos não-PIX, pode usar email da conta ou configuração do sistema
            $recipientEmail = $this->getAccountNotificationEmail($accountData);

            if (! $recipientEmail) {
                $this->logger->info("Nenhum email configurado para notificações da conta", [
                    'withdraw_id' => $withdrawData->id,
                    'account_id' => $accountData->id,
                ]);

                return false;
            }

            $email = (new Email())
                ->from($this->fromAddress)
                ->to($recipientEmail)
                ->subject("Confirmação de Saque - {$this->appName}")
                ->html($this->buildGenericEmailTemplate($withdrawData, $accountData));

            $this->mailer->send($email);

            $this->logger->info("Email genérico de confirmação de saque enviado", [
                'withdraw_id' => $withdrawData->id,
                'account_id' => $accountData->id,
                'recipient' => $recipientEmail,
                'method' => $withdrawData->method->value,
                'amount' => $withdrawData->amount,
                'sent' => true,
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->logger->error("Falha ao enviar email genérico de confirmação", [
                'withdraw_id' => $withdrawData->id,
                'account_id' => $accountData->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Método legado - mantido para compatibilidade
     *
     * @deprecated Use sendWithdrawConfirmationFromDTOs instead
     * @param AccountWithdraw $withdraw
     * @return bool
     */
    public function sendWithdrawConfirmation(AccountWithdraw $withdraw): bool
    {
        try {
            if ($withdraw->pixData === null || $withdraw->pixData->type !== 'email') {
                $this->logger->warning("Tentativa de envio de email para chave PIX não-email", [
                    'withdraw_id' => $withdraw->id,
                    'pix_type' => $withdraw->pixData !== null ? $withdraw->pixData->type : null,
                ]);

                return false;
            }

            $email = (new Email())
                ->from($this->fromAddress)
                ->to($withdraw->pixData->key)
                ->subject("Confirmação de Saque PIX - {$this->appName}")
                ->html($this->buildEmailTemplate($withdraw));

            $this->mailer->send($email);

            $this->logger->info("Email de confirmação de saque enviado", [
                'withdraw_id' => $withdraw->id,
                'recipient' => $withdraw->pixData->key,
                'amount' => $withdraw->amount,
                'sent' => true,
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->logger->error("Falha ao enviar email de confirmação", [
                'withdraw_id' => $withdraw->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Constrói template de email usando DTOs
     *
     * @param AccountWithdrawData $withdrawData DTO do saque
     * @param AccountData $accountData DTO da conta
     * @param AccountWithdrawPixData $pixData DTO dos dados PIX
     * @return string HTML do email
     */
    private function buildEmailTemplateFromDTOs(
        AccountWithdrawData $withdrawData,
        AccountData $accountData,
        AccountWithdrawPixData $pixData,
    ): string {
        $amount = $withdrawData->getFormattedAmount();
        $processedAt = $withdrawData->updatedAt?->format('d/m/Y H:i:s') ?? 'N/A';
        $pixKey = $pixData->getFormattedKey();
        $pixType = $pixData->getTypeLabel();

        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmação de Saque PIX</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #2c5282;'>Saque PIX Realizado com Sucesso</h2>
            
            <div style='background-color: #f7fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #48bb78;'>
                <h3 style='margin-top: 0; color: #2d3748;'>Detalhes do Saque</h3>
                
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Conta:</td>
                        <td style='padding: 8px 0;'>{$accountData->name}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>ID do Saque:</td>
                        <td style='padding: 8px 0;'>{$withdrawData->id}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Valor:</td>
                        <td style='padding: 8px 0; color: #e53e3e; font-weight: bold;'>{$amount}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Chave PIX ({$pixType}):</td>
                        <td style='padding: 8px 0;'>{$pixKey}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Data/Hora:</td>
                        <td style='padding: 8px 0;'>{$processedAt}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Status:</td>
                        <td style='padding: 8px 0; color: #48bb78; font-weight: bold;'>{$withdrawData->getStatusLabel()}</td>
                    </tr>
                </table>
            </div>
            
            <div style='margin-top: 20px; padding: 15px; background-color: #edf2f7; border-radius: 6px;'>
                <p style='margin: 0; font-size: 14px; color: #4a5568;'>
                    <strong>Importante:</strong> Guarde este email como comprovante da transação. 
                    O valor foi debitado da sua conta e enviado para a chave PIX informada.
                </p>
            </div>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;'>
            
            <p style='font-size: 12px; color: #718096; text-align: center;'>
                Este é um email automático. Não responda esta mensagem.<br>
                {$this->appName} - Sistema de Pagamentos
            </p>
        </body>
        </html>
        ";
    }

    /**
     * Constrói template genérico de email
     *
     * @param AccountWithdrawData $withdrawData DTO do saque
     * @param AccountData $accountData DTO da conta
     * @return string HTML do email
     */
    private function buildGenericEmailTemplate(
        AccountWithdrawData $withdrawData,
        AccountData $accountData,
    ): string {
        $amount = $withdrawData->getFormattedAmount();
        $processedAt = $withdrawData->updatedAt?->format('d/m/Y H:i:s') ?? 'N/A';
        $method = $withdrawData->getMethodLabel();

        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmação de Saque</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #2c5282;'>Saque Realizado com Sucesso</h2>
            
            <div style='background-color: #f7fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #48bb78;'>
                <h3 style='margin-top: 0; color: #2d3748;'>Detalhes do Saque</h3>
                
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Conta:</td>
                        <td style='padding: 8px 0;'>{$accountData->name}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>ID do Saque:</td>
                        <td style='padding: 8px 0;'>{$withdrawData->id}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Valor:</td>
                        <td style='padding: 8px 0; color: #e53e3e; font-weight: bold;'>{$amount}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Método:</td>
                        <td style='padding: 8px 0;'>{$method}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Data/Hora:</td>
                        <td style='padding: 8px 0;'>{$processedAt}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Status:</td>
                        <td style='padding: 8px 0; color: #48bb78; font-weight: bold;'>{$withdrawData->getStatusLabel()}</td>
                    </tr>
                </table>
            </div>
            
            <div style='margin-top: 20px; padding: 15px; background-color: #edf2f7; border-radius: 6px;'>
                <p style='margin: 0; font-size: 14px; color: #4a5568;'>
                    <strong>Importante:</strong> Guarde este email como comprovante da transação. 
                    O valor foi debitado da sua conta conforme solicitado.
                </p>
            </div>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;'>
            
            <p style='font-size: 12px; color: #718096; text-align: center;'>
                Este é um email automático. Não responda esta mensagem.<br>
                {$this->appName} - Sistema de Pagamentos
            </p>
        </body>
        </html>
        ";
    }

    /**
     * Template legado - mantido para compatibilidade
     *
     * @deprecated Use buildEmailTemplateFromDTOs instead
     * @param AccountWithdraw $withdraw
     * @return string
     */
    private function buildEmailTemplate(AccountWithdraw $withdraw): string
    {
        $amount = number_format((float) $withdraw->amount, 2, ',', '.');
        $processedAt = $withdraw->updated_at->format('d/m/Y H:i:s');
        $pixKey = $withdraw->pixData->key;
        $pixType = ucfirst($withdraw->pixData->type);

        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmação de Saque PIX</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #2c5282;'>Saque PIX Realizado com Sucesso</h2>
            
            <div style='background-color: #f7fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #48bb78;'>
                <h3 style='margin-top: 0; color: #2d3748;'>Detalhes do Saque</h3>
                
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>ID do Saque:</td>
                        <td style='padding: 8px 0;'>{$withdraw->id}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Valor:</td>
                        <td style='padding: 8px 0; color: #e53e3e; font-weight: bold;'>R$ {$amount}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Chave PIX ({$pixType}):</td>
                        <td style='padding: 8px 0;'>{$pixKey}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Data/Hora:</td>
                        <td style='padding: 8px 0;'>{$processedAt}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold;'>Status:</td>
                        <td style='padding: 8px 0; color: #48bb78; font-weight: bold;'>Processado</td>
                    </tr>
                </table>
            </div>
            
            <div style='margin-top: 20px; padding: 15px; background-color: #edf2f7; border-radius: 6px;'>
                <p style='margin: 0; font-size: 14px; color: #4a5568;'>
                    <strong>Importante:</strong> Guarde este email como comprovante da transação. 
                    O valor foi debitado da sua conta e enviado para a chave PIX informada.
                </p>
            </div>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;'>
            
            <p style='font-size: 12px; color: #718096; text-align: center;'>
                Este é um email automático. Não responda esta mensagem.<br>
                {$this->appName} - Sistema de Pagamentos
            </p>
        </body>
        </html>
        ";
    }

    /**
     * Obtém email para notificações da conta
     *
     * Por enquanto retorna null, mas pode ser estendido para buscar
     * email da conta ou configuração do sistema
     *
     * @param AccountData $accountData DTO da conta
     * @return string|null Email para notificações
     */
    private function getAccountNotificationEmail(AccountData $accountData): ?string
    {
        // TODO: Implementar lógica para obter email da conta
        // Pode ser um campo na conta ou configuração do sistema

        // Por enquanto retorna um email padrão para desenvolvimento
        // @phpstan-ignore-next-line
        if (false) {
            return 'noreply@tecnofit.com.br';
        }

        return null;
    }
}
