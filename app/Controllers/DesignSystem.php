<?php

namespace App\Controllers;

class DesignSystem extends BaseController
{
    public function index()
    {
        return view('design_system/index', [
            'title' => 'Design System',
        ]);
    }
}
