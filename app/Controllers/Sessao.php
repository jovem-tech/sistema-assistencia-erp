<?php

namespace App\Controllers;

class Sessao extends BaseController
{
    public function heartbeat()
    {
        helper('sistema');
        $session = session();

        $payload = [
            'ok' => true,
            'server_time' => time(),
            'timeout_minutes' => get_session_inactivity_minutes(30),
            'last_activity' => $session->get('last_activity'),
        ];

        $this->closeSessionSafely();

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setJSON($payload);
    }

    private function closeSessionSafely(): void
    {
        try {
            session()->close();
            return;
        } catch (\Throwable $e) {
            // segue para o fallback abaixo
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
    }
}
