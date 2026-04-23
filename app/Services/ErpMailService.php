<?php

namespace App\Services;

use App\Models\ConfiguracaoModel;

class ErpMailService
{
    private ConfiguracaoModel $configModel;

    public function __construct()
    {
        $this->configModel = new ConfiguracaoModel();
    }

    public function send(string $toEmail, string $subject, string $htmlBody, ?string $attachmentPath = null, array $overrides = []): array
    {
        $emailDestino = trim($toEmail);
        if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'message' => 'Email de destino invalido.',
                'provider' => 'email',
                'error' => 'destino_invalido',
            ];
        }

        $config = $this->buildConfig($overrides);
        $provider = (string) ($config['protocol'] ?? 'mail');

        try {
            $emailService = \Config\Services::email();
            $emailService->initialize($config);
            $emailService->setFrom($this->resolveFromEmail($config, $overrides), $this->resolveFromName($overrides));
            $emailService->setTo($emailDestino);
            $emailService->setSubject(trim($subject) !== '' ? trim($subject) : 'Mensagem do ERP');
            $emailService->setMessage($htmlBody);

            if ($attachmentPath !== null && $attachmentPath !== '' && is_file($attachmentPath)) {
                $emailService->attach($attachmentPath);
            }

            if ($emailService->send()) {
                return [
                    'ok' => true,
                    'message' => 'Email enviado com sucesso.',
                    'provider' => $provider,
                    'error' => null,
                ];
            }

            return [
                'ok' => false,
                'message' => 'Falha ao enviar email. Verifique a configuracao SMTP.',
                'provider' => $provider,
                'error' => $this->extractDebugger($emailService),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha tecnica ao enviar email.',
                'provider' => $provider,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function buildConfig(array $overrides = []): array
    {
        $smtpHost = trim((string) ($overrides['smtp_host'] ?? $this->configModel->get('smtp_host')));
        $smtpUser = trim((string) ($overrides['smtp_user'] ?? $this->configModel->get('smtp_user')));
        $smtpPass = trim((string) ($overrides['smtp_pass'] ?? $this->configModel->get('smtp_pass')));
        $smtpPort = (int) ($overrides['smtp_port'] ?? $this->configModel->get('smtp_port'));
        $smtpCrypto = strtolower(trim((string) ($overrides['smtp_crypto'] ?? $this->configModel->get('smtp_crypto', 'auto'))));
        $smtpTimeout = (int) ($overrides['smtp_timeout'] ?? $this->configModel->get('smtp_timeout'));
        if ($smtpTimeout <= 0) {
            $smtpTimeout = 20;
        }

        $config = [
            'mailType' => 'html',
            'charset' => 'utf-8',
            'wordWrap' => true,
            'CRLF' => "\r\n",
            'newline' => "\r\n",
            'SMTPTimeout' => $smtpTimeout,
        ];

        if ($smtpHost !== '' && $smtpPort > 0) {
            $config['protocol'] = 'smtp';
            $config['SMTPHost'] = $smtpHost;
            $config['SMTPUser'] = $smtpUser;
            $config['SMTPPass'] = $smtpPass;
            $config['SMTPPort'] = $smtpPort;

            $resolvedCrypto = $this->resolveCrypto($smtpCrypto, $smtpPort);
            if ($resolvedCrypto !== '') {
                $config['SMTPCrypto'] = $resolvedCrypto;
            }
        } else {
            $config['protocol'] = 'mail';
        }

        return $config;
    }

    public function isConfigured(): bool
    {
        return trim((string) $this->configModel->get('smtp_host')) !== ''
            && (int) $this->configModel->get('smtp_port') > 0;
    }

    public function resolveFromEmail(array $config = [], array $overrides = []): string
    {
        $fromEmail = trim((string) ($overrides['smtp_from_email'] ?? $this->configModel->get('smtp_from_email')));
        if ($fromEmail === '') {
            $fromEmail = trim((string) $this->configModel->get('empresa_email'));
        }
        if ($fromEmail === '') {
            $fromEmail = trim((string) ($config['SMTPUser'] ?? ''));
        }
        if ($fromEmail === '') {
            $fromEmail = 'nao-responda@sistema.com';
        }

        return $fromEmail;
    }

    public function resolveFromName(array $overrides = []): string
    {
        $fromName = trim((string) ($overrides['smtp_from_name'] ?? $this->configModel->get('smtp_from_name')));
        if ($fromName === '') {
            $fromName = trim((string) $this->configModel->get('empresa_nome'));
        }
        if ($fromName === '') {
            $fromName = 'Assistencia Tecnica';
        }

        return $fromName;
    }

    private function resolveCrypto(string $smtpCrypto, int $smtpPort): string
    {
        if (in_array($smtpCrypto, ['tls', 'ssl'], true)) {
            return $smtpCrypto;
        }

        if ($smtpCrypto === 'none') {
            return '';
        }

        if ($smtpPort === 465) {
            return 'ssl';
        }

        if ($smtpPort === 587) {
            return 'tls';
        }

        return '';
    }

    private function extractDebugger(object $emailService): string
    {
        if (!method_exists($emailService, 'printDebugger')) {
            return 'email_send_failed';
        }

        $debug = strip_tags((string) $emailService->printDebugger(['headers']));
        $debug = preg_replace('/\s+/', ' ', $debug) ?? '';
        $debug = trim($debug);

        if ($debug === '') {
            return 'email_send_failed';
        }

        if (strlen($debug) > 800) {
            return substr($debug, 0, 800) . '...';
        }

        return $debug;
    }
}
