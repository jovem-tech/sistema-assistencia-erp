<?php

namespace App\Models;

use CodeIgniter\Model;

class OsModel extends Model
{
    protected $table = 'os';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'numero_os', 'cliente_id', 'equipamento_id', 'tecnico_id', 'status', 'estado_fluxo', 'status_atualizado_em',
        'legacy_origem', 'legacy_id', 'numero_os_legado',
        'prioridade', 'relato_cliente', 'diagnostico_tecnico', 'solucao_aplicada', 'procedimentos_executados',
        'data_abertura', 'data_entrada', 'data_previsao', 'data_conclusao', 'data_entrega',
        'valor_mao_obra', 'valor_pecas', 'valor_total', 'desconto', 'valor_final',
        'orcamento_aprovado', 'data_aprovacao', 'orcamento_pdf',
        'acessorios', 'forma_pagamento',
        'garantia_dias', 'garantia_validade',
        'observacoes_internas', 'observacoes_cliente'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['filterEmptyStrings'];
    protected $beforeUpdate = ['filterEmptyStrings'];

    protected function filterEmptyStrings(array $data)
    {
        if (! isset($data['data'])) {
            return $data;
        }

        foreach ($data['data'] as $field => $value) {
            if (is_string($value) && trim($value) === '') {
                $data['data'][$field] = null;
            }
        }

        return $data;
    }

    public function getComplete($id = null)
    {
        $builder = $this->select(
                'os.*,
                clientes.nome_razao as cliente_nome, clientes.telefone1 as cliente_telefone, clientes.email as cliente_email,
                et.nome as equip_tipo, em.nome as equip_marca, emod.nome as equip_modelo,
                equipamentos.numero_serie as equip_serie,
                funcionarios.nome as tecnico_nome'
            )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->join('funcionarios', 'funcionarios.id = os.tecnico_id', 'left');

        if ($id) {
            return $builder->where('os.id', $id)->first();
        }

        return $builder->orderBy('os.created_at', 'DESC')->findAll();
    }

    public function getCompleteByNumeroOs(string $numeroOs)
    {
        $numeroOs = trim($numeroOs);
        if ($numeroOs === '') {
            return null;
        }

        return $this->select(
                'os.*,
                clientes.nome_razao as cliente_nome, clientes.telefone1 as cliente_telefone, clientes.email as cliente_email,
                et.nome as equip_tipo, em.nome as equip_marca, emod.nome as equip_modelo,
                equipamentos.numero_serie as equip_serie, equipamentos.imei as equip_imei,
                funcionarios.nome as tecnico_nome'
            )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->join('funcionarios', 'funcionarios.id = os.tecnico_id', 'left')
            ->groupStart()
                ->where('os.numero_os', $numeroOs)
                ->orWhere('os.numero_os_legado', $numeroOs)
            ->groupEnd()
            ->first();
    }

    public function getByStatus($status)
    {
        return $this->select(
                'os.*, clientes.nome_razao as cliente_nome,
                em.nome as equip_marca, emod.nome as equip_modelo,
                funcionarios.nome as tecnico_nome'
            )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->join('funcionarios', 'funcionarios.id = os.tecnico_id', 'left')
            ->where('os.status', $status)
            ->orderBy('os.created_at', 'DESC')
            ->findAll();
    }

    public function generateNumeroOs()
    {
        $db = \Config\Database::connect();
        $config = $db->table('configuracoes');
        
        $prefixo = $config->where('chave', 'os_prefixo')->get()->getRow()->valor ?? 'OS';
        
        $anoAtualShort = date('y'); // Ex: 26
        $mesAtual = date('m');      // Ex: 03
        
        $configAno = $config->where('chave', 'os_ano')->get()->getRow()->valor ?? '';
        $configMes = $config->where('chave', 'os_mes')->get()->getRow()->valor ?? '';
        
        // Reset sequence if month or year changed
        if ($anoAtualShort !== $configAno || $mesAtual !== $configMes) {
            $novo = 1;
            $db->table('configuracoes')->where('chave', 'os_ultimo_numero')->update(['valor' => $novo]);
            $db->table('configuracoes')->where('chave', 'os_ano')->update(['valor' => $anoAtualShort]);
            
            $checkMes = $db->table('configuracoes')->where('chave', 'os_mes')->get()->getRow();
            if ($checkMes) {
                $db->table('configuracoes')->where('chave', 'os_mes')->update(['valor' => $mesAtual]);
            } else {
                $db->table('configuracoes')->insert(['chave' => 'os_mes', 'valor' => $mesAtual, 'tipo' => 'numero']);
            }
        } else {
            $ultimoRow = $config->where('chave', 'os_ultimo_numero')->get()->getRow();
            $ultimo = (int)($ultimoRow->valor ?? 0);
            $novo = $ultimo + 1;
            $db->table('configuracoes')->where('chave', 'os_ultimo_numero')->update(['valor' => $novo]);
        }
        
        return $prefixo . $anoAtualShort . $mesAtual . str_pad($novo, 4, '0', STR_PAD_LEFT);
    }

    public function getDashboardStats()
    {
        $db = \Config\Database::connect();

        if ($db->fieldExists('estado_fluxo', 'os')) {
            return [
                'total_abertas'      => (int)$db->table('os')->whereNotIn('estado_fluxo', ['encerrado', 'cancelado'])->countAllResults(),
                'aguardando_analise' => (int)$db->table('os')->where('status', 'triagem')->countAllResults(),
                'em_reparo'          => (int)$db->table('os')->whereIn('status', ['reparo_execucao', 'aguardando_reparo', 'retrabalho'])->countAllResults(),
                'prontas'            => (int)$db->table('os')->where('estado_fluxo', 'pronto')->countAllResults(),
                'entregues_hoje'     => (int)$db->table('os')->where('status', 'entregue_reparado')->where('DATE(data_entrega)', date('Y-m-d'))->countAllResults(),
                'faturamento_mes'    => (float)($db->table('os')
                                        ->selectSum('valor_final')
                                        ->where('status', 'entregue_reparado')
                                        ->where('MONTH(data_entrega)', date('m'))
                                        ->where('YEAR(data_entrega)', date('Y'))
                                        ->get()->getRow()->valor_final ?? 0),
            ];
        }

        return [
            'total_abertas'      => (int)$db->table('os')->whereNotIn('status', ['entregue','cancelado'])->countAllResults(),
            'aguardando_analise' => (int)$db->table('os')->where('status', 'aguardando_analise')->countAllResults(),
            'em_reparo'          => (int)$db->table('os')->where('status', 'em_reparo')->countAllResults(),
            'prontas'            => (int)$db->table('os')->where('status', 'pronto')->countAllResults(),
            'entregues_hoje'     => (int)$db->table('os')->where('status', 'entregue')->where('DATE(data_entrega)', date('Y-m-d'))->countAllResults(),
            'faturamento_mes'    => (float)($db->table('os')
                                        ->selectSum('valor_final')
                                        ->where('status', 'entregue')
                                        ->where('MONTH(data_entrega)', date('m'))
                                        ->where('YEAR(data_entrega)', date('Y'))
                                        ->get()->getRow()->valor_final ?? 0),
        ];
    }

    public function getDashboardYears(): array
    {
        $db = \Config\Database::connect();
        $dateColumns = [];

        if ($db->fieldExists('data_abertura', 'os')) {
            $dateColumns[] = 'data_abertura';
        }
        if ($db->fieldExists('created_at', 'os')) {
            $dateColumns[] = 'created_at';
        }
        if ($db->fieldExists('data_entrada', 'os')) {
            $dateColumns[] = 'data_entrada';
        }
        if ($db->fieldExists('data_entrega', 'os')) {
            $dateColumns[] = 'data_entrega';
        }

        if (empty($dateColumns)) {
            return [(int) date('Y')];
        }

        $selectParts = [];
        foreach ($dateColumns as $column) {
            $selectParts[] = "SELECT YEAR($column) AS ano FROM os WHERE $column IS NOT NULL";
        }

        $sql = sprintf(
            'SELECT DISTINCT ano FROM (%s) anos WHERE ano IS NOT NULL ORDER BY ano DESC',
            implode(' UNION ALL ', $selectParts)
        );

        $rows = $db->query($sql)->getResultArray();
        if (empty($rows)) {
            return [(int) date('Y')];
        }

        return array_map(
            static fn(array $row): int => (int) ($row['ano'] ?? date('Y')),
            $rows
        );
    }

    public function getDashboardDateExpression(): ?string
    {
        $db = \Config\Database::connect();

        $hasDataAbertura = $db->fieldExists('data_abertura', 'os');
        $hasCreatedAt = $db->fieldExists('created_at', 'os');
        $hasDataEntrada = $db->fieldExists('data_entrada', 'os');
        $hasDataEntrega = $db->fieldExists('data_entrega', 'os');

        if ($hasDataAbertura && $hasCreatedAt) {
            return 'COALESCE(data_abertura, created_at)';
        }

        if ($hasDataAbertura) {
            return 'data_abertura';
        }

        if ($hasCreatedAt) {
            return 'created_at';
        }

        if ($hasDataEntrada) {
            return 'data_entrada';
        }

        if ($hasDataEntrega) {
            return 'data_entrega';
        }

        return null;
    }

    public function getRecentes($limit = 10)
    {
        return $this->select(
                'os.*, clientes.nome_razao as cliente_nome,
                em.nome as equip_marca, emod.nome as equip_modelo'
            )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->orderBy('os.created_at', 'DESC')
            ->findAll($limit);
    }
}
