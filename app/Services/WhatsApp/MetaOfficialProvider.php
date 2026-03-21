<?php

namespace App\Services\WhatsApp;

class MetaOfficialProvider implements BulkMessageProviderInterface
{
    public function sendCampaign(array $phones, string $message, array $context = []): array
    {
        return [
            'ok' => false,
            'provider' => 'meta_oficial',
            'message' => 'Provider de envios em massa (Meta oficial) ainda nao implementado nesta fase.',
            'response' => null,
        ];
    }
}

