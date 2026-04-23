<?php

namespace App\Controllers;

class AtendimentoMobile extends BaseController
{
    public function __construct()
    {
        requirePermission('clientes', 'visualizar');
    }

    public function index()
    {
        $preview = (string) $this->request->getGet('preview') === '1';
        if (!$preview && !$this->isLikelyMobileDevice()) {
            return redirect()->to('/dashboard')->with(
                'error',
                'Modulo mobile exclusivo para celulares. Use ?preview=1 para validar no desktop.'
            );
        }

        $target = trim((string) get_config('mobile_pwa_url', '/atendimento-mobile-app/login'));
        if ($target === '') {
            $target = '/atendimento-mobile-app/login';
        }

        if (str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
            return redirect()->to($target);
        }

        if (!str_starts_with($target, '/')) {
            $target = '/' . $target;
        }

        return redirect()->to($target);
    }

    private function isLikelyMobileDevice(): bool
    {
        $ua = strtolower((string) $this->request->getUserAgent()->getAgentString());
        if ($ua === '') {
            return false;
        }

        return str_contains($ua, 'android')
            || str_contains($ua, 'iphone')
            || str_contains($ua, 'ipad')
            || str_contains($ua, 'ipod')
            || str_contains($ua, 'mobile')
            || str_contains($ua, 'opera mini')
            || str_contains($ua, 'windows phone');
    }
}
