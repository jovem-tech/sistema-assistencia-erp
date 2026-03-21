<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversaTagModel extends Model
{
    protected $table = 'conversa_tags';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'conversa_id',
        'tag_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

