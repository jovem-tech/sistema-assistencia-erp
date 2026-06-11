<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $companyName = trim((string) get_config('empresa_nome', 'Jovem Tech'));
        $systemName = trim((string) get_config('sistema_nome', 'Jovem Tech ERP'));
        $companyPhone = trim((string) get_config('empresa_telefone', ''));
        $companyEmail = trim((string) get_config('empresa_email', ''));
        $companyAddress = trim((string) get_config('empresa_endereco', ''));
        $whatsPhone = trim((string) get_config('whatsapp_gateway_account_number', get_config('whatsapp_test_phone', $companyPhone)));
        $mobileUrl = trim((string) get_config('mobile_pwa_url', '/atendimento-mobile-app/login'));
        $whatsAppHref = $this->buildWhatsAppHref(
            $whatsPhone,
            'Olá! Quero agendar uma demonstração do ERP de assistência técnica.'
        );

        return view('public/landing_page', [
            'systemName' => $systemName !== '' ? $systemName : 'Jovem Tech ERP',
            'companyName' => $companyName !== '' ? $companyName : 'Jovem Tech',
            'companyPhone' => $companyPhone,
            'companyEmail' => $companyEmail,
            'companyAddress' => $companyAddress,
            'logoUrl' => $this->resolveSystemAssetUrl('sistema_logo'),
            'faviconUrl' => $this->resolveSystemAssetUrl('sistema_icone'),
            'loginUrl' => base_url('login'),
            'mobileUrl' => $this->normalizeAppUrl($mobileUrl, base_url('login')),
            'whatsAppHref' => $whatsAppHref,
            'primaryCtaHref' => $whatsAppHref ?: '#recursos',
            'primaryCtaLabel' => $whatsAppHref !== null ? 'Agendar demonstração' : 'Ver módulos principais',
            'systemVersion' => get_system_version(),
        ]);
    }

    private function resolveSystemAssetUrl(string $configKey): ?string
    {
        $fileName = trim((string) get_config($configKey, ''));
        if ($fileName === '') {
            return null;
        }

        $baseName = basename($fileName);
        $fullPath = FCPATH . 'uploads/sistema/' . $baseName;
        if (!is_file($fullPath)) {
            return null;
        }

        return base_url('uploads/sistema/' . rawurlencode($baseName));
    }

    private function buildWhatsAppHref(string $phone, string $message): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if (!str_starts_with($digits, '55') && strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        if (strlen($digits) < 12 || strlen($digits) > 13) {
            return null;
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
    }

    private function normalizeAppUrl(string $url, string $fallback): string
    {
        if ($url === '') {
            return $fallback;
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return base_url(ltrim($url, '/'));
    }
}
