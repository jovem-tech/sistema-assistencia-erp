<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroModel extends Model
{
    protected $table = 'financeiro';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'os_id', 'tipo', 'categoria', 'descricao', 'valor',
        'forma_pagamento', 'status', 'data_vencimento', 'data_pagamento', 'observacoes'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getResumoMensal($mes = null, $ano = null)
    {
        $mes = $mes ?? date('m');
        $ano = $ano ?? date('Y');

        $db = \Config\Database::connect();

        $receitas = $db->table('financeiro')
            ->selectSum('valor')
            ->where('tipo', 'receber')
            ->where('status', 'pago')
            ->where('MONTH(data_pagamento)', $mes)
            ->where('YEAR(data_pagamento)', $ano)
            ->get()->getRow()->valor ?? 0;

        $despesas = $db->table('financeiro')
            ->selectSum('valor')
            ->where('tipo', 'pagar')
            ->where('status', 'pago')
            ->where('MONTH(data_pagamento)', $mes)
            ->where('YEAR(data_pagamento)', $ano)
            ->get()->getRow()->valor ?? 0;

        $pendentes = $db->table('financeiro')
            ->selectSum('valor')
            ->where('tipo', 'receber')
            ->where('status', 'pendente')
            ->get()->getRow()->valor ?? 0;

        return [
            'receitas' => (float)$receitas,
            'despesas' => (float)$despesas,
            'lucro'    => (float)$receitas - (float)$despesas,
            'pendentes' => (float)$pendentes,
        ];
    }

    public function getVencidas()
    {
        return $this->where('status', 'pendente')
                    ->where('data_vencimento <', date('Y-m-d'))
                    ->orderBy('data_vencimento', 'ASC')
                    ->findAll();
    }
}
