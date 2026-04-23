<?php

namespace App\Services\WhatsApp;

class NullBulkProvider implements BulkMessageProviderInterface
{
    public function sendCampaign(array $phones, string $message, array $context = []): array
    {
        return [
            'ok' => false,
            'provider' => 'null_bulk',
            'message' => 'Envio em massa nao configurado.',
            'response' => null,
        ];
    }
}

