<?php

namespace App\Services\WhatsApp;

class NullProvider implements WhatsAppProviderInterface
{
    public function sendText(string $phone, string $message, array $context = []): array
    {
        return [
            'ok' => false,
            'provider' => 'null',
            'message' => 'Envio desabilitado: provedor de WhatsApp nao configurado.',
            'response' => null,
        ];
    }

    public function sendFile(string $phone, string $filePath, string $message = '', array $context = []): array
    {
        return [
            'ok' => false,
            'provider' => 'null',
            'message' => 'Envio de arquivo desabilitado: provedor de WhatsApp nao configurado.',
            'response' => null,
        ];
    }

    public function testConnection(?string $phone = null): array
    {
        return [
            'ok' => false,
            'provider' => 'null',
            'message' => 'Provedor de WhatsApp desabilitado.',
            'response' => null,
        ];
    }
}
