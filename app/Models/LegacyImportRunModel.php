<?php

namespace App\Models;

use CodeIgniter\Model;

class LegacyImportRunModel extends Model
{
    protected $table = 'legacy_import_runs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'source_name',
        'mode',
        'status',
        'started_at',
        'finished_at',
        'summary_json',
        'notes',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
