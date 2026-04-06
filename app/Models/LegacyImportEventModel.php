<?php

namespace App\Models;

use CodeIgniter\Model;

class LegacyImportEventModel extends Model
{
    protected $table = 'legacy_import_events';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'run_id',
        'entity',
        'severity',
        'action',
        'legacy_id',
        'message',
        'details_json',
        'created_at',
    ];
    protected $useTimestamps = false;
}
