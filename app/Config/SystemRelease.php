<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class SystemRelease extends BaseConfig
{
    /**
     * Versao oficial exibida no rodape do ERP.
     * Atualize este valor em cada release.
     */
    public string $version = '2.16.17';
}
