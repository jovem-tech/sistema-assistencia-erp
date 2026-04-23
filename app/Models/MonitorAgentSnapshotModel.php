<?php

namespace App\Models;

use CodeIgniter\Model;

class MonitorAgentSnapshotModel extends Model
{
    protected $table = 'monitor_agent_snapshots';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $allowedFields = [
        'agent_id',
        'payload_json',
        'hostname',
        'serial_number',
        'collected_at',
        'received_at',
    ];
}