<?php

namespace App\Models;

use CodeIgniter\Model;

class OsPdfTemplateModel extends Model
{
    protected $table = 'os_pdf_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'codigo',
        'nome',
        'descricao',
        'conteudo_html',
        'ativo',
        'ordem',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getActive(): array
    {
        if (!$this->db->tableExists($this->table)) {
            return [];
        }

        return $this->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->orderBy('nome', 'ASC')
            ->findAll();
    }

    public function getAllOrdered(): array
    {
        if (!$this->db->tableExists($this->table)) {
            return [];
        }

        return $this->orderBy('ordem', 'ASC')
            ->orderBy('nome', 'ASC')
            ->findAll();
    }

    public function byCode(string $codigo): ?array
    {
        if (!$this->db->tableExists($this->table)) {
            return null;
        }

        return $this->where('codigo', trim($codigo))->first();
    }
}
