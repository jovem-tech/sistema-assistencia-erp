<?php

namespace App\Services;

use App\Models\ConfiguracaoModel;

class OrcamentoMailService
{
    private ConfiguracaoModel $configModel;

    public function __construct()
    {
        $this->configModel = new ConfiguracaoModel();
    }

    public function send(string $toEmail, string $subject, string $htmlBody, ?string $attachmentPath = null): array
    {
        $emailDestino = trim($toEmail);
        if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'message' => 'Email de destino invalido para envio do orcamento.',
                'provider' => 'email',
                'error' => 'destino_invalido',
            ];
        }

        $config = $this->buildConfig();
        $provider = (string) ($config['protocol'] ?? 'mail');

        try {
            $emailService = \Config\Services::email();
            $emailService->initialize($config);

            $fromEmail = trim((string) $this->configModel->get('empresa_email'));
            if ($fromEmail === '') {
                $fromEmail = trim((string) ($config['SMTPUser'] ?? ''));
            }
            if ($fromEmail === '') {
                $fromEmail = 'nao-responda@sistema.com';
            }

            $fromName = trim((string) $this->configModel->get('empresa_nome'));
            if ($fromName === '') {
                $fromName = 'Assistencia Tecnica';
            }

            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setTo($emailDestino);
            $emailService->setSubject(trim($subject) !== '' ? trim($subject) : 'Orcamento');
            $emailService->setMessage($htmlBody);

            if ($attachmentPath !== null && $attachmentPath !== '' && is_file($attachmentPath)) {
                $emailService->attach($attachmentPath);
            }

            $sent = $emailService->send();
            if ($sent) {
                return [
                    'ok' => true,
                    'message' => 'Email enviado com sucesso.',
                    'provider' => $provider,
                    'error' => null,
                ];
            }

            $debug = '';
            if (method_exists($emailService, 'printDebugger')) {
                $debug = strip_tags((string) $emailService->printDebugger(['headers']));
                $debug = preg_replace('/\s+/', ' ', $debug) ?? '';
                $debug = trim($debug);
                if (strlen($debug) > 800) {
                    $debug = substr($debug, 0, 800) . '...';
                }
            }

            return [
                'ok' => false,
                'message' => 'Falha ao enviar email do orcamento. Verifique a configuracao SMTP.',
                'provider' => $provider,
                'error' => $debug !== '' ? $debug : 'email_send_failed',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha tecnica ao enviar email do orcamento.',
                'provider' => $provider,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function buildConfig(): array
    {
        $smtpHost = trim((string) $this->configModel->get('smtp_host'));
        $smtpUser = trim((string) $this->configModel->get('smtp_user'));
        $smtpPass = trim((string) $this->configModel->get('smtp_pass'));
        $smtpPort = (int) $this->configModel->get('smtp_port');

        $config = [
            'mailType' => 'html',
            'charset' => 'utf-8',
            'wordWrap' => true,
            'CRLF' => "\r\n",
            'newline' => "\r\n",
            'SMTPTimeout' => 20,
        ];

        if ($smtpHost !== '' && $smtpPort > 0) {
            $config['protocol'] = 'smtp';
            $config['SMTPHost'] = $smtpHost;
            $config['SMTPUser'] = $smtpUser;
            $config['SMTPPass'] = $smtpPass;
            $config['SMTPPort'] = $smtpPort;

            if ($smtpPort === 465) {
                $config['SMTPCrypto'] = 'ssl';
            } elseif ($smtpPort === 587) {
                $config['SMTPCrypto'] = 'tls';
            }
        } else {
            $config['protocol'] = 'mail';
        }

        return $config;
    }
}
