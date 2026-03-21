<?php

namespace App\Models;

use CodeIgniter\Model;

class DefeitoModel extends Model
{
    protected $table = 'equipamentos_defeitos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSãoftDeletes = false;
    protected $allowedFields = ['nãome', 'tipo_id', 'classificacao', 'descricao', 'ativo'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nãome'          => 'required|max_length[150]',
        'tipo_id'       => 'required|integer',
        'classificacao' => 'required|in_list[hardware,sãoftware]',
    ];

    public function getWithTipo()
    {
        return $this->select('equipamentos_defeitos.*, equipamentos_tipos.nãome as tipo_nãome, COUNT(edp.id) as qtd_procedimentos')
                    ->join('equipamentos_tipos', 'equipamentos_tipos.id = equipamentos_defeitos.tipo_id')
                    ->join('equipamento_defeito_procedimentos edp', 'edp.defeito_id = equipamentos_defeitos.id', 'left')
                    ->groupBy('equipamentos_defeitos.id')
                    ->orderBy('equipamentos_tipos.nãome', 'ASC')
                    ->orderBy('equipamentos_defeitos.classificacao', 'ASC')
                    ->orderBy('equipamentos_defeitos.nãome', 'ASC')
                    ->findAll();
    }

    public function getByTipo($tipo_id)
    {
        return $this->where('tipo_id', $tipo_id)
                    ->where('ativo', 1)
                    ->orderBy('classificacao', 'ASC')
                    ->orderBy('nãome', 'ASC')
                    ->findAll();
    }

    public function getByOs($os_id)
    {
        $db = \Config\Database::connect();
        return $db->table('os_defeitos od')
                  ->select('od.defeito_id, ed.nãome, ed.classificacao, ed.descricao, et.nãome as tipo_nãome')
                  ->join('equipamentos_defeitos ed', 'ed.id = od.defeito_id')
                  ->join('equipamentos_tipos et', 'et.id = ed.tipo_id')
                  ->where('od.os_id', $os_id)
                  ->get()->getResultArray();
    }

    public function saveOsDefeitos($os_id, array $defeito_ids)
    {
        $db = \Config\Database::connect();
        // Limpa os defeitos anteriores da OS
        $db->table('os_defeitos')->where('os_id', $os_id)->delete();

        if (!empty($defeito_ids)) {
            $inserts = [];
            foreach ($defeito_ids as $did) {
                $inserts[] = ['os_id' => $os_id, 'defeito_id' => (int)$did];
            }
            $db->table('os_defeitos')->insertBatch($inserts);
        }
    }
}
