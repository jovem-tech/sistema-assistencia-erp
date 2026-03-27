<?php

namespace App\Controllers;

class Sessao extends BaseController
{
    public function heartbeat()
    {
        helper('sistema');

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setJSON([
                'ok' => true,
                'server_time' => time(),
                'timeout_minutes' => get_session_inactivity_minutes(30),
                'last_activity' => session()->get('last_activity'),
            ]);
    }
}
