<?php

namespace App\Models;

use App\Services\EquipamentoProfileService;
use CodeIgniter\Model;

class EquipamentoModel extends Model
{
    protected $table = 'equipamentos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'cliente_id', 'tipo_id', 'marca_id', 'modelo_id', 'cor', 'cor_hex', 'cor_rgb', 'numero_serie',
        'imei', 'senha_acesso', 'estado_fisico', 'acessorios', 'observacoes',
        'desktop_modalidade', 'gabinete_tipo', 'gabinete_identificacao_status', 'gabinete_observacao',
        'placa_mae', 'chipset', 'processador', 'memoria_ram', 'armazenamento', 'placa_video',
        'fonte_alimentacao', 'resumo_tecnico', 'configuracao_status', 'configuracao_origem',
        'configuracao_detectada_em',
        'status_operacional', 'motivo_encerramento', 'observacao_encerramento', 'encerrado_em',
        'legacy_origem', 'legacy_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'cliente_id' => 'required|integer',
        'tipo_id'    => 'required|integer',
    ];
    protected $afterFind = ['appendDerivedFields'];

    public function getByCliente($clienteId, bool $operacionaisOnly = false, array $includeIds = [])
    {
        $builder = $this->select("equipamentos.*, tipos.nome as tipo_nome, marcas.nome as marca_nome, modelos.nome as modelo_nome, (SELECT ef.arquivo FROM equipamentos_fotos ef WHERE ef.equipamento_id = equipamentos.id ORDER BY ef.is_principal DESC, ef.id ASC LIMIT 1) AS foto_principal_arquivo, " . $this->buildOpenOsCountSelectExpression())
                    ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
                    ->join('equipamentos_marcas marcas', 'marcas.id = equipamentos.marca_id', 'left')
                    ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left')
                    ->join('equipamento_clientes ec', 'ec.equipamento_id = equipamentos.id', 'left')
                    ->groupStart()
                        ->where('equipamentos.cliente_id', $clienteId)
                        ->orWhere('ec.cliente_id', $clienteId)
                    ->groupEnd()
                    ->groupBy('equipamentos.id');

        if ($operacionaisOnly && $this->supportsOperationalLifecycle()) {
            $includeIds = array_values(array_unique(array_filter(array_map(
                static fn($item): int => (int) $item,
                $includeIds
            ), static fn(int $item): bool => $item > 0)));

            $builder->groupStart()
                ->groupStart()
                    ->where('equipamentos.status_operacional', 'ativo')
                    ->where('equipamentos.encerrado_em IS NULL', null, false)
                ->groupEnd();

            if (!empty($includeIds)) {
                $builder->orWhereIn('equipamentos.id', $includeIds);
            }

            $builder->groupEnd();
        }

        return $builder
            ->orderBy('equipamentos.created_at', 'DESC')
            ->findAll();
    }

    public function getWithCliente($id = null)
    {
        $builder = $this->select("equipamentos.*, clientes.nome_razao as cliente_nome, tipos.nome as tipo_nome, marcas.nome as marca_nome, modelos.nome as modelo_nome, (SELECT ef.arquivo FROM equipamentos_fotos ef WHERE ef.equipamento_id = equipamentos.id ORDER BY ef.is_principal DESC, ef.id ASC LIMIT 1) AS foto_principal_arquivo, " . $this->buildOpenOsCountSelectExpression())
                        ->join('clientes', 'clientes.id = equipamentos.cliente_id', 'left')
                        ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
                        ->join('equipamentos_marcas marcas', 'marcas.id = equipamentos.marca_id', 'left')
                        ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left');

        if ($id) {
            return $builder->where('equipamentos.id', $id)->first();
        }

        return $builder->orderBy('equipamentos.created_at', 'DESC')->findAll();
    }

    public function supportsOperationalLifecycle(): bool
    {
        return $this->db->fieldExists('status_operacional', $this->table)
            && $this->db->fieldExists('encerrado_em', $this->table);
    }

    public function countOpenOsByEquipamentoId(int $equipamentoId): int
    {
        if ($equipamentoId <= 0 || ! $this->db->tableExists('os')) {
            return 0;
        }

        $builder = $this->db->table('os')
            ->where('equipamento_id', $equipamentoId);

        if ($this->db->fieldExists('estado_fluxo', 'os')) {
            $builder->groupStart()
                ->where('estado_fluxo IS NULL', null, false)
                ->orWhere("TRIM(COALESCE(estado_fluxo, '')) = ''", null, false)
                ->orWhereNotIn('estado_fluxo', ['encerrado', 'cancelado'])
            ->groupEnd();
        } else {
            $builder->whereNotIn('status', ['entregue_reparado', 'devolvido_sem_reparo', 'descartado', 'cancelado']);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    protected function appendDerivedFields(array $data): array
    {
        $profileService = new EquipamentoProfileService();

        if (!isset($data['data'])) {
            return $data;
        }

        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            $data['data'] = array_map(
                static fn(array $row): array => $profileService->appendDerivedFields($row),
                $data['data']
            );
            return $data;
        }

        if (is_array($data['data'])) {
            $data['data'] = $profileService->appendDerivedFields($data['data']);
        }

        return $data;
    }

    private function buildOpenOsCountSelectExpression(): string
    {
        if (! $this->db->tableExists('os')) {
            return '0 AS os_abertas_count';
        }

        if ($this->db->fieldExists('estado_fluxo', 'os')) {
            return "(SELECT COUNT(*) FROM os o WHERE o.equipamento_id = equipamentos.id AND (o.estado_fluxo IS NULL OR TRIM(COALESCE(o.estado_fluxo, '')) = '' OR o.estado_fluxo NOT IN ('encerrado', 'cancelado'))) AS os_abertas_count";
        }

        return "(SELECT COUNT(*) FROM os o WHERE o.equipamento_id = equipamentos.id AND o.status NOT IN ('entregue_reparado', 'devolvido_sem_reparo', 'descartado', 'cancelado')) AS os_abertas_count";
    }
}
