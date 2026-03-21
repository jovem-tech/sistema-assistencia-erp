<?php

namespace App\Services\WhatsApp;

interface WhatsAppProviderInterface
{
    public function sendText(string $phone, string $message, array $context = []): array;

    public function sendFile(string $phone, string $filePath, string $message = '', array $context = []): array;

    public function testConnection(?string $phone = null): array;
}
