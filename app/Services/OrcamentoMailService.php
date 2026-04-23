<?php

namespace App\Services;

class OrcamentoMailService
{
    private ErpMailService $mailService;

    public function __construct()
    {
        $this->mailService = new ErpMailService();
    }

    public function send(string $toEmail, string $subject, string $htmlBody, ?string $attachmentPath = null): array
    {
        $result = $this->mailService->send($toEmail, $subject, $htmlBody, $attachmentPath);
        if (!empty($result['ok'])) {
            return $result;
        }

        $result['message'] = 'Falha ao enviar email do orcamento. Verifique a configuracao SMTP.';
        return $result;
    }
}
