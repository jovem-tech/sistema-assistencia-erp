<?php

namespace App\Libraries;

use App\Models\OsModel;
use App\Models\ClienteModel;
use App\Models\EquipamentoModel;
use App\Models\MensagemWhatsappModel;
use App\Models\ServicoModel;
use App\Models\PecaModel;
use App\Models\CrmInteracaoModel;
use App\Models\UsuarioModel;

class GlobalSearchService
{
    /**
     * Catálogo de módulos/páginas do sistema para busca rápida
     */
    private $modules = [
        ['name' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'bi-speedometer2', 'category' => 'Módulo', 'permission' => 'dashboard:visualizar'],
        ['name' => 'Ordens de Serviço', 'url' => 'os', 'icon' => 'bi-file-earmark-text', 'category' => 'Módulo', 'permission' => 'os:visualizar'],
        ['name' => 'Nova Ordem de Serviço (OS)', 'url' => 'os/nova', 'icon' => 'bi-plus-circle', 'category' => 'Ação', 'permission' => 'os:criar'],
        ['name' => 'Clientes', 'url' => 'clientes', 'icon' => 'bi-people', 'category' => 'Módulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Novo Cliente', 'url' => 'clientes/novo', 'icon' => 'bi-person-plus', 'category' => 'Ação', 'permission' => 'clientes:criar'],
        ['name' => 'Serviços', 'url' => 'servicos', 'icon' => 'bi-tools', 'category' => 'Módulo', 'permission' => 'servicos:visualizar'],
        ['name' => 'Estoque / Peças', 'url' => 'estoque', 'icon' => 'bi-box-seam', 'category' => 'Módulo', 'permission' => 'estoque:visualizar'],
        ['name' => 'Equipamentos / Aparelhos', 'url' => 'equipamentos', 'icon' => 'bi-laptop', 'category' => 'Módulo', 'permission' => 'equipamentos:visualizar'],
        ['name' => 'Central de Mensagens WhatsApp', 'url' => 'atendimento-whatsapp', 'icon' => 'bi-whatsapp', 'category' => 'Módulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Inbox WhatsApp', 'url' => 'atendimento-whatsapp/conversas', 'icon' => 'bi-chat-left-text', 'category' => 'Módulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Chatbot / Automação', 'url' => 'atendimento-whatsapp/chatbot', 'icon' => 'bi-robot', 'category' => 'Módulo', 'permission' => 'clientes:editar'],
        ['name' => 'FAQ / Base de Conhecimento', 'url' => 'atendimento-whatsapp/faq', 'icon' => 'bi-question-circle', 'category' => 'Módulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'CRM / Timeline', 'url' => 'crm/timeline', 'icon' => 'bi-graph-up', 'category' => 'Módulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Configurações do Sistema', 'url' => 'configuracoes', 'icon' => 'bi-gear', 'category' => 'Módulo', 'permission' => 'configuracoes:visualizar'],
        ['name' => 'Usuários e Permissões', 'url' => 'usuarios', 'icon' => 'bi-people-fill', 'category' => 'Módulo', 'permission' => 'usuarios:visualizar'],
        ['name' => 'Financeiro', 'url' => 'financeiro', 'icon' => 'bi-cash-stack', 'category' => 'Módulo', 'permission' => 'financeiro:visualizar'],
        ['name' => 'Relatórios', 'url' => 'relatorios', 'icon' => 'bi-bar-chart', 'category' => 'Módulo', 'permission' => 'relatorios:visualizar'],
    ];

    /**
     * Executa a busca em todos os módulos permitidos
     */
    public function search(string $term, string $filter = 'all'): array
    {
        $results = [];
        $activeFilters = explode(',', $filter);
        $term = trim($term);

        // Busca em Módulos
        if (in_array('all', $activeFilters) || in_array('modules', $activeFilters)) {
            $modules = $this->searchModules($term);
            if (!empty($modules)) {
                $results['Módulos e Ações'] = $modules;
            }
        }

        // Busca em OS
        if (in_array('all', $activeFilters) || in_array('os', $activeFilters)) {
            if (can('os', 'visualizar')) {
                $os = $this->searchOs($term);
                if (!empty($os)) {
                    $results['Ordens de Serviço'] = $os;
                }
            }
        }

        // Busca em Clientes
        if (in_array('all', $activeFilters) || in_array('clientes', $activeFilters)) {
            if (can('clientes', 'visualizar')) {
                $clientes = $this->searchClientes($term);
                if (!empty($clientes)) {
                    $results['Clientes'] = $clientes;
                }
            }
        }

        // Busca em Equipamentos
        if (in_array('all', $activeFilters) || in_array('equipamentos', $activeFilters)) {
            if (can('equipamentos', 'visualizar')) {
                $equipamentos = $this->searchEquipamentos($term);
                if (!empty($equipamentos)) {
                    $results['Equipamentos'] = $equipamentos;
                }
            }
        }

        // Busca em WhatsApp
        if (in_array('all', $activeFilters) || in_array('whatsapp', $activeFilters)) {
            if (can('clientes', 'visualizar')) { // WhatsApp usa permissão de clientes
                $whatsapp = $this->searchWhatsapp($term);
                if (!empty($whatsapp)) {
                    $results['Mensagens WhatsApp'] = $whatsapp;
                }
            }
        }

        // Busca em Serviços
        if (in_array('all', $activeFilters) || in_array('servicos', $activeFilters)) {
            if (can('servicos', 'visualizar')) {
                $servicos = $this->searchServicos($term);
                if (!empty($servicos)) {
                    $results['Serviços'] = $servicos;
                }
            }
        }

        // Busca em Peças
        if (in_array('all', $activeFilters) || in_array('pecas', $activeFilters)) {
            if (can('estoque', 'visualizar')) {
                $pecas = $this->searchPecas($term);
                if (!empty($pecas)) {
                    $results['Estoque / Peças'] = $pecas;
                }
            }
        }

        return $results;
    }

    private function searchModules(string $term): array
    {
        $found = [];
        $termLower = mb_strtolower($term);

        foreach ($this->modules as $mod) {
            if (str_contains(mb_strtolower($mod['name']), $termLower)) {
                // Verificar permissão
                $permParts = explode(':', $mod['permission']);
                if (count($permParts) === 2) {
                    if (can($permParts[0], $permParts[1])) {
                        $found[] = [
                            'title' => $mod['name'],
                            'subtitle' => $mod['category'],
                            'url' => base_url($mod['url']),
                            'icon' => $mod['icon'],
                            'badge' => 'Sistema'
                        ];
                    }
                }
            }
        }

        return $found;
    }

    private function searchOs(string $term): array
    {
        $model = new OsModel();
        $termClean = str_replace(['OS', 'os'], '', $term);

        $query = $model->select('os.*, clientes.nome_razao as cliente_nome, et.nome as equip_tipo, em.nome as equip_marca, emod.nome as equip_modelo')
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->groupStart()
                ->like('os.numero_os', $term)
                ->orLike('os.numero_os', $termClean)
                ->orLike('clientes.nome_razao', $term)
                ->orLike('os.relato_cliente', $term)
                ->orLike('equipamentos.numero_serie', $term)
                ->orLike('emod.nome', $term)
            ->groupEnd()
            ->limit(10)
            ->find();

        $found = [];
        foreach ($query as $row) {
            $found[] = [
                'title' => $row['numero_os'],
                'subtitle' => $row['cliente_nome'] . ' - ' . ($row['equip_modelo'] ?? $row['equip_tipo']),
                'url' => base_url('os/visualizar/' . $row['id']),
                'icon' => 'bi-file-earmark-text',
                'badge' => $row['status']
            ];
        }
        return $found;
    }

    private function searchClientes(string $term): array
    {
        $model = new ClienteModel();
        $query = $model->groupStart()
                ->like('nome_razao', $term)
                ->orLike('cpf_cnpj', $term)
                ->orLike('telefone1', $term)
                ->orLike('email', $term)
            ->groupEnd()
            ->limit(10)
            ->find();

        $found = [];
        foreach ($query as $row) {
            $found[] = [
                'title' => $row['nome_razao'],
                'subtitle' => ($row['cpf_cnpj'] ?? $row['email'] ?? $row['telefone1']),
                'url' => base_url('clientes/visualizar/' . $row['id']),
                'icon' => 'bi-person',
                'badge' => 'Cliente'
            ];
        }
        return $found;
    }

    private function searchEquipamentos(string $term): array
    {
        $model = new EquipamentoModel();
        $query = $model->select('equipamentos.*, tipos.nome as tipo_nome, marcas.nome as marca_nome, modelos.nome as modelo_nome, clientes.nome_razao as cliente_nome')
            ->join('clientes', 'clientes.id = equipamentos.cliente_id', 'left')
            ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas marcas', 'marcas.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left')
            ->groupStart()
                ->like('equipamentos.numero_serie', $term)
                ->orLike('equipamentos.imei', $term)
                ->orLike('modelos.nome', $term)
                ->orLike('clientes.nome_razao', $term)
            ->groupEnd()
            ->limit(10)
            ->find();

        $found = [];
        foreach ($query as $row) {
            $found[] = [
                'title' => ($row['marca_nome'] . ' ' . $row['modelo_nome']),
                'subtitle' => 'S/N: ' . ($row['numero_serie'] ?? 'N/I') . ' - Cli: ' . $row['cliente_nome'],
                'url' => base_url('equipamentos/visualizar/' . $row['id']),
                'icon' => 'bi-laptop',
                'badge' => $row['tipo_nome']
            ];
        }
        return $found;
    }

    private function searchWhatsapp(string $term): array
    {
        $model = new MensagemWhatsappModel();
        $query = $model->select('mensagens_whatsapp.*, clientes.nome_razao as cliente_nome')
            ->join('clientes', 'clientes.id = mensagens_whatsapp.cliente_id', 'left')
            ->like('mensagens_whatsapp.mensagem', $term)
            ->orLike('mensagens_whatsapp.telefone', $term)
            ->orderBy('mensagens_whatsapp.created_at', 'DESC')
            ->limit(10)
            ->find();

        $found = [];
        foreach ($query as $row) {
            $found[] = [
                'title' => mb_strimwidth($row['mensagem'], 0, 50, '...'),
                'subtitle' => ($row['cliente_nome'] ?? $row['telefone']) . ' - ' . date('d/m/Y H:i', strtotime($row['created_at'])),
                'url' => base_url('atendimento-whatsapp?conversa_id=' . $row['conversa_id']),
                'icon' => 'bi-whatsapp',
                'badge' => 'Mensagem'
            ];
        }
        return $found;
    }

    private function searchServicos(string $term): array
    {
        $model = new ServicoModel();
        $query = $model->like('nome', $term)
            ->orLike('descricao', $term)
            ->limit(5)
            ->find();

        $found = [];
        foreach ($query as $row) {
            $found[] = [
                'title' => $row['nome'],
                'subtitle' => 'Valor: R$ ' . number_format($row['valor'], 2, ',', '.'),
                'url' => base_url('servicos/editar/' . $row['id']),
                'icon' => 'bi-tools',
                'badge' => 'Serviço'
            ];
        }
        return $found;
    }

    private function searchPecas(string $term): array
    {
        $model = new PecaModel();
        $query = $model->like('nome', $term)
            ->orLike('codigo', $term)
            ->orLike('modelos_compativeis', $term)
            ->limit(10)
            ->find();

        $found = [];
        foreach ($query as $row) {
            $found[] = [
                'title' => $row['nome'],
                'subtitle' => 'Estoque: ' . $row['quantidade_atual'] . ' - Preço Venda: R$ ' . number_format($row['preco_venda'], 2, ',', '.'),
                'url' => base_url('estoque/editar/' . $row['id']),
                'icon' => 'bi-box-seam',
                'badge' => 'Peça'
            ];
        }
        return $found;
    }
}
