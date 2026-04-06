<?php

namespace App\Models;

use CodeIgniter\Model;

class LegacyImportAliasModel extends Model
{
    protected $table = 'legacy_import_aliases';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'source_name',
        'source_entity',
        'source_legacy_id',
        'target_entity',
        'target_id',
        'match_key_type',
        'match_key_value',
        'resolution_strategy',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
