<?php

namespace App\Services\WhatsApp;

interface BulkMessageProviderInterface
{
    public function sendCampaign(array $phones, string $message, array $context = []): array;
}

