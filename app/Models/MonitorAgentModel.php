<?php

namespace App\Models;

use CodeIgniter\Model;

class MonitorAgentModel extends Model
{
    protected $table = 'monitor_agents';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $allowedFields = [
        'agent_uuid',
        'installation_id',
        'usuario_id',
        'cliente_id',
        'equipamento_id',
        'os_id',
        'numero_os',
        'label',
        'api_token_hash',
        'api_token_name',
        'api_token_expira_em',
        'hostname',
        'serial_number',
        'manufacturer',
        'model',
        'motherboard',
        'bios_version',
        'cpu',
        'ram_gb',
        'windows_caption',
        'windows_version',
        'windows_build',
        'ultimo_bootstrap_em',
        'ultimo_checkin_em',
        'ultimo_snapshot_em',
        'ativo',
    ];
}