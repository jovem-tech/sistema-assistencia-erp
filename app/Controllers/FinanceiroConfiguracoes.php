<?php

namespace App\Controllers;

use App\Models\FinanceiroCategoriaModel;
use App\Models\FinanceiroDreGrupoModel;
use App\Models\FinanceiroDreSubgrupoModel;
use App\Models\LogModel;

class FinanceiroConfiguracoes extends BaseController
{
    private FinanceiroCategoriaModel $categoriaModel;
    private FinanceiroDreGrupoModel $grupoModel;
    private FinanceiroDreSubgrupoModel $subgrupoModel;

    public function __construct()
    {
        requirePermission('financeiro', 'visualizar');
        $this->categoriaModel = new FinanceiroCategoriaModel();
        $this->grupoModel = new FinanceiroDreGrupoModel();
        $this->subgrupoModel = new FinanceiroDreSubgrupoModel();
    }

    public function index()
    {
        return view('financeiro/configuracoes', [
            'title' => 'Configuracoes Financeiras',
            'categorias' => $this->categoriaModel->isTableReady() ? $this->categoriaModel->getAllComDefaults() : [],
            'dre_grupos' => $this->grupoModel->isTableReady() ? $this->grupoModel->orderBy('ordem_exibicao', 'ASC')->orderBy('nome', 'ASC')->findAll() : [],
            'dre_subgrupos' => $this->subgrupoModel->isTableReady() ? $this->subgrupoModel->getAllWithGroups() : [],
        ]);
    }

    public function saveCategoria()
    {
        requirePermission('financeiro', 'editar');
        $db = \Config\Database::connect();

        $id = (int) ($this->request->getPost('id') ?? 0);
        $nome = trim((string) $this->request->getPost('nome'));
        $tipo = strtolower(trim((string) $this->request->getPost('tipo')));

        if ($nome === '' || ! in_array($tipo, ['receber', 'pagar', 'ambos'], true)) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Informe uma categoria valida.');
        }

        $duplicate = $this->categoriaModel
            ->where('LOWER(nome) = ' . $db->escape(mb_strtolower($nome, 'UTF-8')), null, false)
            ->where('tipo', $tipo)
            ->where('id !=', $id)
            ->first();

        if (is_array($duplicate)) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Ja existe uma categoria com esse nome para esse tipo.');
        }

        $grupoId = (int) ($this->request->getPost('dre_grupo_id') ?? 0);
        $subgrupoId = (int) ($this->request->getPost('dre_subgrupo_id') ?? 0);
        $subgrupo = $subgrupoId > 0 ? $this->subgrupoModel->find($subgrupoId) : null;
        if ($subgrupoId > 0 && (! is_array($subgrupo) || ($grupoId > 0 && (int) ($subgrupo['grupo_id'] ?? 0) !== $grupoId))) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'O subgrupo selecionado nao pertence ao grupo informado.');
        }

        if ($grupoId <= 0 && is_array($subgrupo)) {
            $grupoId = (int) ($subgrupo['grupo_id'] ?? 0);
        }

        $payload = [
            'nome' => $nome,
            'tipo' => $tipo,
            'dre_grupo_id' => $grupoId > 0 ? $grupoId : null,
            'dre_subgrupo_id' => $subgrupoId > 0 ? $subgrupoId : null,
            'impacta_dre_padrao' => (int) ($this->request->getPost('impacta_dre_padrao') ?? 0),
            'impacta_fluxo_caixa_padrao' => (int) ($this->request->getPost('impacta_fluxo_caixa_padrao') ?? 0),
            'dre_fixo_mensal_padrao' => (int) ($this->request->getPost('dre_fixo_mensal_padrao') ?? 0),
            'ordem_exibicao' => (int) ($this->request->getPost('ordem_exibicao') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ?? 0),
        ];

        if ($id > 0) {
            $this->categoriaModel->update($id, $payload);
            LogModel::registrar('financeiro_categoria_atualizada', 'Categoria financeira atualizada: ' . $nome);
            return redirect()->to('/financeiro/configuracoes')->with('success', 'Categoria financeira atualizada com sucesso.');
        }

        $this->categoriaModel->insert($payload);
        LogModel::registrar('financeiro_categoria_criada', 'Categoria financeira criada: ' . $nome);

        return redirect()->to('/financeiro/configuracoes')->with('success', 'Categoria financeira criada com sucesso.');
    }

    public function deleteCategoria(int $id)
    {
        requirePermission('financeiro', 'excluir');

        $row = $this->categoriaModel->find($id);
        if (! is_array($row)) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Categoria financeira nao encontrada.');
        }

        $this->categoriaModel->delete($id);
        LogModel::registrar('financeiro_categoria_excluida', 'Categoria financeira excluida: ' . ($row['nome'] ?? ''));

        return redirect()->to('/financeiro/configuracoes')->with('success', 'Categoria financeira excluida com sucesso.');
    }

    public function saveGrupo()
    {
        requirePermission('financeiro', 'editar');
        $db = \Config\Database::connect();

        $id = (int) ($this->request->getPost('id') ?? 0);
        $nome = trim((string) $this->request->getPost('nome'));

        if ($nome === '') {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Informe um grupo DRE valido.');
        }

        $duplicate = $this->grupoModel
            ->where('LOWER(nome) = ' . $db->escape(mb_strtolower($nome, 'UTF-8')), null, false)
            ->where('id !=', $id)
            ->first();

        if (is_array($duplicate)) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Ja existe um grupo DRE com esse nome.');
        }

        $payload = [
            'nome' => $nome,
            'descricao' => trim((string) $this->request->getPost('descricao')),
            'ordem_exibicao' => (int) ($this->request->getPost('ordem_exibicao') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ?? 0),
        ];

        if ($payload['descricao'] === '') {
            $payload['descricao'] = null;
        }

        if ($id > 0) {
            $this->grupoModel->update($id, $payload);
            LogModel::registrar('financeiro_dre_grupo_atualizado', 'Grupo DRE atualizado: ' . $nome);
            return redirect()->to('/financeiro/configuracoes')->with('success', 'Grupo DRE atualizado com sucesso.');
        }

        $this->grupoModel->insert($payload);
        LogModel::registrar('financeiro_dre_grupo_criado', 'Grupo DRE criado: ' . $nome);

        return redirect()->to('/financeiro/configuracoes')->with('success', 'Grupo DRE criado com sucesso.');
    }

    public function deleteGrupo(int $id)
    {
        requirePermission('financeiro', 'excluir');

        $row = $this->grupoModel->find($id);
        if (! is_array($row)) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Grupo DRE nao encontrado.');
        }

        $this->grupoModel->delete($id);
        LogModel::registrar('financeiro_dre_grupo_excluido', 'Grupo DRE excluido: ' . ($row['nome'] ?? ''));

        return redirect()->to('/financeiro/configuracoes')->with('success', 'Grupo DRE excluido com sucesso.');
    }

    public function saveSubgrupo()
    {
        requirePermission('financeiro', 'editar');
        $db = \Config\Database::connect();

        $id = (int) ($this->request->getPost('id') ?? 0);
        $nome = trim((string) $this->request->getPost('nome'));
        $grupoId = (int) ($this->request->getPost('grupo_id') ?? 0);

        if ($nome === '' || $grupoId <= 0) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Informe grupo e subgrupo DRE validos.');
        }

        $duplicate = $this->subgrupoModel
            ->where('grupo_id', $grupoId)
            ->where('LOWER(nome) = ' . $db->escape(mb_strtolower($nome, 'UTF-8')), null, false)
            ->where('id !=', $id)
            ->first();

        if (is_array($duplicate)) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Ja existe um subgrupo com esse nome dentro do grupo selecionado.');
        }

        $payload = [
            'grupo_id' => $grupoId,
            'nome' => $nome,
            'descricao' => trim((string) $this->request->getPost('descricao')),
            'ordem_exibicao' => (int) ($this->request->getPost('ordem_exibicao') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ?? 0),
        ];

        if ($payload['descricao'] === '') {
            $payload['descricao'] = null;
        }

        if ($id > 0) {
            $this->subgrupoModel->update($id, $payload);
            LogModel::registrar('financeiro_dre_subgrupo_atualizado', 'Subgrupo DRE atualizado: ' . $nome);
            return redirect()->to('/financeiro/configuracoes')->with('success', 'Subgrupo DRE atualizado com sucesso.');
        }

        $this->subgrupoModel->insert($payload);
        LogModel::registrar('financeiro_dre_subgrupo_criado', 'Subgrupo DRE criado: ' . $nome);

        return redirect()->to('/financeiro/configuracoes')->with('success', 'Subgrupo DRE criado com sucesso.');
    }

    public function deleteSubgrupo(int $id)
    {
        requirePermission('financeiro', 'excluir');

        $row = $this->subgrupoModel->find($id);
        if (! is_array($row)) {
            return redirect()->to('/financeiro/configuracoes')->with('error', 'Subgrupo DRE nao encontrado.');
        }

        $this->subgrupoModel->delete($id);
        LogModel::registrar('financeiro_dre_subgrupo_excluido', 'Subgrupo DRE excluido: ' . ($row['nome'] ?? ''));

        return redirect()->to('/financeiro/configuracoes')->with('success', 'Subgrupo DRE excluido com sucesso.');
    }
}
