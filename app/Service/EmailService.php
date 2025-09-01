<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AccountWithdraw;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class EmailService
{
    private LoggerInterface $logger;
    private Swift_Mailer $mailer;
    private string $appName;
    private string $fromAddress;
    private string $fromName;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('email', 'default');
        
        // Configuração do Mailhog para desenvolvimento
        $transport = (new Swift_SmtpTransport('mailhog', 1025))
            ->setUsername(null)
            ->setPassword(null);
            
        $this->mailer = new Swift_Mailer($transport);
        
        // Configurações obtidas diretamente do env() ou valores padrão
        $this->appName = env('APP_NAME', 'TecnofitPixAPI');
        $this->fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@tecnofit.com');
        $this->fromName = env('MAIL_FROM_NAME', 'Tecnofit PIX API');
    }

    public function sendWithdrawConfirmation(AccountWithdraw $withdraw): bool
    {
        try {
            if (!$withdraw->pixData || $withdraw->pixData->type !== 'email') {
                $this->logger->warning("Tentativa de envio de email para chave PIX não-email", [
                    'withdraw_id' => $withdraw->id,
                    'pix_type' => $withdraw->pixData?->type
                ]);
                return false;
            }

            $message = (new Swift_Message())
                ->setSubject("Confirmação de Saque PIX - {$this->appName}")
                ->setFrom([$this->fromAddress => $this->fromName])
                ->setTo([$withdraw->pixData->key])
                ->setBody($this->buildEmailTemplate($withdraw), 'text/html');

            $result = $this->mailer->send($message);

            $this->logger->info("Email de confirmação de saque enviado", [
                'withdraw_id' => $withdraw->id,
                'recipient' => $withdraw->pixData->key,
                'amount' => $withdraw->amount,
                'sent' => $result > 0
            ]);

            return $result > 0;

        } catch (\Throwable $e) {
            $this->logger->error("Falha ao enviar email de confirmação", [
                'withdraw_id' => $withdraw->id,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);

            throw $e;
        }
    }

    private function buildEmailTemplate(AccountWithdraw $withdraw): string
    {
        $amount = number_format((float) $withdraw->amount, 2, ',', '.');
        $processedAt = $withdraw->updated_at?->format('d/m/Y H:i:s') ?? 'N/A';
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
}
