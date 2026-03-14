<?php

namespace App\Controllers;

class Vendas extends BaseController
{
    public function __construct()
    {
        requirePermission('vendas');
    }

    public function index()
    {
        $data = [
            'title' => 'Vendas',
        ];
        return view('vendas/index', $data);
    }
}
