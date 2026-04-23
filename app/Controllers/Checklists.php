<?php

namespace App\Controllers;

use App\Models\ChecklistItemModel;
use App\Models\ChecklistModeloModel;
use App\Models\ChecklistTipoModel;
use App\Models\EquipamentoTipoModel;
use Config\Database;

class Checklists extends BaseController
{
    public function __construct()
    {
        requirePermission('os');
    }

    public function entrada()
    {
        if (!$this->checklistInfraReady()) {
            return redirect()->to('/os')->with('error', 'Infraestrutura de checklist ainda nao foi migrada. Execute: php spark migrate.');
        }

        $tipoEntrada = $this->getChecklistTipoEntrada();
        if ($tipoEntrada === null) {
            return redirect()->to('/os')->with('error', 'Checklist de Entrada ainda nao foi configurado no banco.');
        }

        $modeloModel = new ChecklistModeloModel();
        $itemModel = new ChecklistItemModel();

        $modelos = $modeloModel
            ->select('checklist_modelos.*, equipamentos_tipos.nome AS tipo_equipamento_nome')
            ->join('equipamentos_tipos', 'equipamentos_tipos.id = checklist_modelos.tipo_equipamento_id', 'left')
            ->where('checklist_modelos.checklist_tipo_id', (int) $tipoEntrada['id'])
            ->orderBy('equipamentos_tipos.nome', 'ASC')
            ->orderBy('checklist_modelos.ordem', 'ASC')
            ->findAll();

        $selectedModeloId = (int) ($this->request->getGet('modelo_id') ?? 0);
        if ($selectedModeloId <= 0 && !empty($modelos)) {
            $selectedModeloId = (int) ($modelos[0]['id'] ?? 0);
        }

        $selectedModelo = null;
        $itens = [];
        if ($selectedModeloId > 0) {
            foreach ($modelos as $modelo) {
                if ((int) ($modelo['id'] ?? 0) === $selectedModeloId) {
                    $selectedModelo = $modelo;
                    break;
                }
            }
            $itens = $itemModel
                ->where('checklist_modelo_id', $selectedModeloId)
                ->orderBy('ordem', 'ASC')
                ->findAll();
        }

        $tiposEquipamento = (new EquipamentoTipoModel())
            ->orderBy('nome', 'ASC')
            ->findAll();

        return view('checklists/entrada', [
            'title' => 'Checklist de Entrada',
            'checklistTipo' => $tipoEntrada,
            'modelos' => $modelos,
            'selectedModelo' => $selectedModelo,
            'selectedModeloId' => $selectedModeloId,
            'itens' => $itens,
            'tiposEquipamento' => $tiposEquipamento,
        ]);
    }

    public function salvarEntrada()
    {
        if (!$this->checklistInfraReady()) {
            return redirect()->back()->with('error', 'Infraestrutura de checklist indisponivel. Execute: php spark migrate.');
        }

        $tipoEntrada = $this->getChecklistTipoEntrada();
        if ($tipoEntrada === null) {
            return redirect()->back()->with('error', 'Checklist de Entrada nao encontrado.');
        }

        $rules = [
            'tipo_equipamento_id' => 'required|integer',
            'nome' => 'required|min_length[3]|max_length[160]',
            'ordem' => 'permit_empty|integer',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $modeloModel = new ChecklistModeloModel();
        $modeloId = (int) ($this->request->getPost('modelo_id') ?? 0);
        $tipoEquipamentoId = (int) ($this->request->getPost('tipo_equipamento_id') ?? 0);

        $payload = [
            'checklist_tipo_id' => (int) $tipoEntrada['id'],
            'tipo_equipamento_id' => $tipoEquipamentoId,
            'nome' => trim((string) $this->request->getPost('nome')),
            'descricao' => trim((string) ($this->request->getPost('descricao') ?? '')) ?: null,
            'ordem' => (int) ($this->request->getPost('ordem') ?? 0),
            'ativo' => $this->request->getPost('ativo') ? 1 : 0,
        ];

        if ($modeloId > 0) {
            $modeloModel->update($modeloId, $payload);
            return redirect()->to('/checklists/entrada?modelo_id=' . $modeloId)
                ->with('success', 'Checklist de Entrada atualizado com sucesso.');
        }

        $existing = $modeloModel
            ->where('checklist_tipo_id', (int) $tipoEntrada['id'])
            ->where('tipo_equipamento_id', $tipoEquipamentoId)
            ->first();

        if ($existing) {
            $existingId = (int) ($existing['id'] ?? 0);
            $modeloModel->update($existingId, $payload);
            return redirect()->to('/checklists/entrada?modelo_id=' . $existingId)
                ->with('success', 'Checklist de Entrada atualizado para o tipo selecionado.');
        }

        $modeloModel->insert($payload);
        $novoId = (int) $modeloModel->getInsertID();

        return redirect()->to('/checklists/entrada?modelo_id=' . $novoId)
            ->with('success', 'Checklist de Entrada criado com sucesso.');
    }

    public function salvarItemEntrada()
    {
        if (!$this->checklistInfraReady()) {
            return redirect()->back()->with('error', 'Infraestrutura de checklist indisponivel. Execute: php spark migrate.');
        }

        $rules = [
            'checklist_modelo_id' => 'required|integer',
            'descricao' => 'required|min_length[2]|max_length[255]',
            'ordem' => 'permit_empty|integer',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $itemModel = new ChecklistItemModel();
        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $modeloId = (int) ($this->request->getPost('checklist_modelo_id') ?? 0);
        $payload = [
            'checklist_modelo_id' => $modeloId,
            'descricao' => trim((string) $this->request->getPost('descricao')),
            'ordem' => (int) ($this->request->getPost('ordem') ?? 0),
            'ativo' => $this->request->getPost('ativo') ? 1 : 0,
        ];

        if ($itemId > 0) {
            $itemModel->update($itemId, $payload);
            return redirect()->to('/checklists/entrada?modelo_id=' . $modeloId)
                ->with('success', 'Item de checklist atualizado.');
        }

        $itemModel->insert($payload);
        return redirect()->to('/checklists/entrada?modelo_id=' . $modeloId)
            ->with('success', 'Item de checklist criado.');
    }

    public function removerItemEntrada($itemId)
    {
        if (!$this->checklistInfraReady()) {
            return redirect()->back()->with('error', 'Infraestrutura de checklist indisponivel. Execute: php spark migrate.');
        }

        $itemId = (int) $itemId;
        $itemModel = new ChecklistItemModel();
        $item = $itemModel->find($itemId);
        if (!$item) {
            return redirect()->back()->with('error', 'Item de checklist nao encontrado.');
        }

        $modeloId = (int) ($item['checklist_modelo_id'] ?? 0);
        $itemModel->delete($itemId);

        return redirect()->to('/checklists/entrada?modelo_id=' . $modeloId)
            ->with('success', 'Item removido do checklist.');
    }

    public function manutencao()
    {
        return view('checklists/placeholder', [
            'title' => 'Checklist de Manutencao',
            'nomeModulo' => 'Checklist de Manutencao',
        ]);
    }

    public function controleQualidade()
    {
        return view('checklists/placeholder', [
            'title' => 'Checklist Controle da Qualidade',
            'nomeModulo' => 'Checklist Controle da Qualidade',
        ]);
    }

    public function saida()
    {
        return view('checklists/placeholder', [
            'title' => 'Checklist de Saida',
            'nomeModulo' => 'Checklist de Saida',
        ]);
    }

    private function getChecklistTipoEntrada(): ?array
    {
        return (new ChecklistTipoModel())->findByCodigo('entrada');
    }

    private function checklistInfraReady(): bool
    {
        try {
            $db = Database::connect();
            foreach (['checklist_tipos', 'checklist_modelos', 'checklist_itens'] as $table) {
                if (!$db->tableExists($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', '[Checklist] Falha ao validar infraestrutura: ' . $e->getMessage());
            return false;
        }
    }
}
