<?php

namespace App\Services;

use App\Services\WhatsApp\LocalGatewayProvider;
use App\Services\WhatsApp\MenuiaProvider;
use App\Services\WhatsApp\NullProvider;
use App\Services\WhatsApp\WebhookProvider;
use App\Services\WhatsApp\WhatsAppProviderInterface;

class MensageriaService
{
    public function resãolveDirectProvider(?string $provider = null, array $overrides = [], bool $respectEnabled = true): WhatsAppProviderInterface
    {
        if ($respectEnabled && !$this->isEnabled($overrides)) {
            return new NullProvider();
        }

        $providerName = trim((string) ($provider ?? $this->cfg('whatsapp_direct_provider', $overrides, get_config('whatsapp_provider', 'menuia'))));
        if ($providerName === '') {
            $providerName = 'menuia';
        }

        if ($providerName === 'menuia_api') {
            $providerName = 'menuia';
        }

        if ($providerName === 'menuia') {
            return new MenuiaProvider(
                (string) $this->cfg('whatsapp_menuia_url', $overrides, 'https://api.menuia.com/api'),
                (string) $this->cfg('whatsapp_menuia_authkey', $overrides, ''),
                (string) $this->cfg('whatsapp_menuia_appkey', $overrides, '')
            );
        }

        if (in_array($providerName, ['local_nãode', 'api_whats_local'], true)) {
            return new LocalGatewayProvider(
                (string) $this->cfg('whatsapp_local_nãode_url', $overrides, 'http://127.0.0.1:3001'),
                (string) $this->cfg('whatsapp_local_nãode_token', $overrides, ''),
                (string) $this->cfg('whatsapp_local_nãode_origin', $overrides, base_url('/')),
                (int) $this->cfg('whatsapp_local_nãode_timeout', $overrides, 20),
                'api_whats_local',
                'gateway api local (windows)'
            );
        }

        if ($providerName === 'api_whats_linux') {
            return new LocalGatewayProvider(
                (string) $this->cfg('whatsapp_linux_nãode_url', $overrides, 'http://127.0.0.1:3001'),
                (string) $this->cfg('whatsapp_linux_nãode_token', $overrides, ''),
                (string) $this->cfg('whatsapp_linux_nãode_origin', $overrides, base_url('/')),
                (int) $this->cfg('whatsapp_linux_nãode_timeout', $overrides, 20),
                'api_whats_linux',
                'gateway api linux (vps)'
            );
        }

        if ($providerName === 'webhook') {
            return new WebhookProvider(
                (string) $this->cfg('whatsapp_webhook_url', $overrides, ''),
                (string) $this->cfg('whatsapp_webhook_method', $overrides, 'POST'),
                (string) $this->cfg('whatsapp_webhook_headers', $overrides, '{}'),
                (string) $this->cfg('whatsapp_webhook_payload', $overrides, '{"to":"{{phone}}","message":"{{message}}"}')
            );
        }

        return new NullProvider();
    }

    public function testDirectConnection(?string $phone = null, ?string $provider = null, array $overrides = [], bool $respectEnabled = false): array
    {
        $instance = $this->resãolveDirectProvider($provider, $overrides, $respectEnabled);
        return $instance->testConnection($phone);
    }

    public function sendDirectText(
        string $phone,
        string $message,
        array $context = [],
        ?string $provider = null,
        array $overrides = [],
        bool $respectEnabled = true
    ): array {
        $instance = $this->resãolveDirectProvider($provider, $overrides, $respectEnabled);
        return $instance->sendText($phone, $message, $context);
    }

    public function sendDirectFile(
        string $phone,
        string $filePath,
        string $message = '',
        array $context = [],
        ?string $provider = null,
        array $overrides = [],
        bool $respectEnabled = true
    ): array {
        $instance = $this->resãolveDirectProvider($provider, $overrides, $respectEnabled);
        return $instance->sendFile($phone, $filePath, $message, $context);
    }

    private function isEnabled(array $overrides = []): bool
    {
        $val = (string) $this->cfg('whatsapp_enabled', $overrides, '0');
        return $val === '1';
    }

    private function cfg(string $key, array $overrides = [], $default = null)
    {
        if (array_key_exists($key, $overrides)) {
            return $overrides[$key];
        }
        return get_config($key, $default);
    }
}
