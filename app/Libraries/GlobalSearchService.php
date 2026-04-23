<?php

namespace App\Libraries;

use App\Models\ClienteModel;
use App\Models\EquipamentoModel;
use App\Models\MensagemWhatsappModel;
use App\Models\OsModel;
use App\Models\PecaModel;
use App\Models\ServicoModel;

class GlobalSearchService
{
    /**
     * Catalogo de modulos/paginas do sistema para busca rapida
     *
     * @var array<int, array<string, string>>
     */
    private array $modules = [
        ['name' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'bi-speedometer2', 'category' => 'Modulo', 'permission' => 'dashboard:visualizar'],
        ['name' => 'Ordens de Servico', 'url' => 'os', 'icon' => 'bi-file-earmark-text', 'category' => 'Modulo', 'permission' => 'os:visualizar'],
        ['name' => 'OS Legado (numero antigo)', 'url' => 'os?legado=1', 'icon' => 'bi-clock-history', 'category' => 'Filtro', 'permission' => 'os:visualizar'],
        ['name' => 'Nova Ordem de Servico (OS)', 'url' => 'os/nova', 'icon' => 'bi-plus-circle', 'category' => 'Acao', 'permission' => 'os:criar'],
        ['name' => 'Clientes', 'url' => 'clientes', 'icon' => 'bi-people', 'category' => 'Modulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Novo Cliente', 'url' => 'clientes/novo', 'icon' => 'bi-person-plus', 'category' => 'Acao', 'permission' => 'clientes:criar'],
        ['name' => 'Servicos', 'url' => 'servicos', 'icon' => 'bi-tools', 'category' => 'Modulo', 'permission' => 'servicos:visualizar'],
        ['name' => 'Estoque / Pecas', 'url' => 'estoque', 'icon' => 'bi-box-seam', 'category' => 'Modulo', 'permission' => 'estoque:visualizar'],
        ['name' => 'Equipamentos / Aparelhos', 'url' => 'equipamentos', 'icon' => 'bi-laptop', 'category' => 'Modulo', 'permission' => 'equipamentos:visualizar'],
        ['name' => 'Central de Mensagens WhatsApp', 'url' => 'atendimento-whatsapp', 'icon' => 'bi-whatsapp', 'category' => 'Modulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Inbox WhatsApp', 'url' => 'atendimento-whatsapp/conversas', 'icon' => 'bi-chat-left-text', 'category' => 'Modulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Chatbot / Automacao', 'url' => 'atendimento-whatsapp/chatbot', 'icon' => 'bi-robot', 'category' => 'Modulo', 'permission' => 'clientes:editar'],
        ['name' => 'FAQ / Base de Conhecimento', 'url' => 'atendimento-whatsapp/faq', 'icon' => 'bi-question-circle', 'category' => 'Modulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'CRM / Timeline', 'url' => 'crm/timeline', 'icon' => 'bi-graph-up', 'category' => 'Modulo', 'permission' => 'clientes:visualizar'],
        ['name' => 'Configuracoes do Sistema', 'url' => 'configuracoes', 'icon' => 'bi-gear', 'category' => 'Modulo', 'permission' => 'configuracoes:visualizar'],
        ['name' => 'Usuarios e Permissoes', 'url' => 'usuarios', 'icon' => 'bi-people-fill', 'category' => 'Modulo', 'permission' => 'usuarios:visualizar'],
        ['name' => 'Financeiro', 'url' => 'financeiro', 'icon' => 'bi-cash-stack', 'category' => 'Modulo', 'permission' => 'financeiro:visualizar'],
        ['name' => 'Relatorios', 'url' => 'relatorios', 'icon' => 'bi-bar-chart', 'category' => 'Modulo', 'permission' => 'relatorios:visualizar'],
    ];

    /**
     * Executa a busca em todos os modulos permitidos
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function search(string $term, string $filter = 'all'): array
    {
        $results = [];
        $activeFilters = array_values(array_filter(array_map('trim', explode(',', $filter))));
        $term = trim($term);

        if ($term === '') {
            return $results;
        }

        if (in_array('all', $activeFilters, true) || in_array('modules', $activeFilters, true)) {
            $modules = $this->searchModules($term);
            if (!empty($modules)) {
                $results['Modulos e Acoes'] = $modules;
            }
        }

        if (in_array('all', $activeFilters, true) || in_array('os', $activeFilters, true)) {
            if (can('os', 'visualizar')) {
                $os = $this->searchOs($term);
                if (!empty($os)) {
                    $results['Ordens de Servico'] = $os;
                }
            }
        }

        if (in_array('os_legado', $activeFilters, true)) {
            if (can('os', 'visualizar')) {
                $legacyOs = $this->searchLegacyOs($term);
                if (!empty($legacyOs)) {
                    $results['OS Legado'] = $legacyOs;
                }
            }
        }

        if (in_array('all', $activeFilters, true) || in_array('clientes', $activeFilters, true)) {
            if (can('clientes', 'visualizar')) {
                $clientes = $this->searchClientes($term);
                if (!empty($clientes)) {
                    $results['Clientes'] = $clientes;
                }
            }
        }

        if (in_array('all', $activeFilters, true) || in_array('equipamentos', $activeFilters, true)) {
            if (can('equipamentos', 'visualizar')) {
                $equipamentos = $this->searchEquipamentos($term);
                if (!empty($equipamentos)) {
                    $results['Equipamentos'] = $equipamentos;
                }
            }
        }

        if (in_array('all', $activeFilters, true) || in_array('whatsapp', $activeFilters, true)) {
            if (can('clientes', 'visualizar')) {
                $whatsapp = $this->searchWhatsapp($term);
                if (!empty($whatsapp)) {
                    $results['Mensagens WhatsApp'] = $whatsapp;
                }
            }
        }

        if (in_array('all', $activeFilters, true) || in_array('servicos', $activeFilters, true)) {
            if (can('servicos', 'visualizar')) {
                $servicos = $this->searchServicos($term);
                if (!empty($servicos)) {
                    $results['Servicos'] = $servicos;
                }
            }
        }

        if (in_array('all', $activeFilters, true) || in_array('pecas', $activeFilters, true)) {
            if (can('estoque', 'visualizar')) {
                $pecas = $this->searchPecas($term);
                if (!empty($pecas)) {
                    $results['Estoque / Pecas'] = $pecas;
                }
            }
        }

        return $results;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchModules(string $term): array
    {
        $found = [];
        $termLower = mb_strtolower($term);

        foreach ($this->modules as $mod) {
            if (!str_contains(mb_strtolower($mod['name']), $termLower)) {
                continue;
            }

            $permParts = explode(':', $mod['permission']);
            if (count($permParts) !== 2) {
                continue;
            }

            if (!can($permParts[0], $permParts[1])) {
                continue;
            }

            $found[] = [
                'title' => $mod['name'],
                'subtitle' => $mod['category'],
                'url' => base_url($mod['url']),
                'icon' => $mod['icon'],
                'badge' => 'Sistema',
            ];
        }

        return $found;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchOs(string $term): array
    {
        $model = new OsModel();
        $termClean = str_replace(['OS', 'os'], '', $term);

        $rows = $model->select('os.*, clientes.nome_razao as cliente_nome, et.nome as equip_tipo, em.nome as equip_marca, emod.nome as equip_modelo')
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->groupStart()
                ->like('os.numero_os', $term)
                ->orLike('os.numero_os', $termClean)
                ->orLike('os.numero_os_legado', $term)
                ->orLike('os.numero_os_legado', $termClean)
                ->orLike('clientes.nome_razao', $term)
                ->orLike('os.relato_cliente', $term)
                ->orLike('equipamentos.numero_serie', $term)
                ->orLike('emod.nome', $term)
            ->groupEnd()
            ->limit(10)
            ->find();

        return $this->mapOsResults($rows, 'bi-file-earmark-text', false);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchLegacyOs(string $term): array
    {
        $model = new OsModel();
        $termClean = str_replace(['OS', 'os'], '', $term);

        $rows = $model->select('os.*, clientes.nome_razao as cliente_nome, et.nome as equip_tipo, em.nome as equip_marca, emod.nome as equip_modelo')
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->where("TRIM(COALESCE(os.numero_os_legado, '')) <> ''", null, false)
            ->groupStart()
                ->like('os.numero_os_legado', $term)
                ->orLike('os.numero_os_legado', $termClean)
                ->orLike('os.numero_os', $term)
                ->orLike('os.numero_os', $termClean)
                ->orLike('clientes.nome_razao', $term)
                ->orLike('os.relato_cliente', $term)
                ->orLike('equipamentos.numero_serie', $term)
                ->orLike('emod.nome', $term)
            ->groupEnd()
            ->limit(10)
            ->find();

        return $this->mapOsResults($rows, 'bi-clock-history', true);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string>>
     */
    private function mapOsResults(array $rows, string $icon, bool $legacyOnly): array
    {
        $found = [];

        foreach ($rows as $row) {
            $subtitleParts = [
                $row['cliente_nome'] ?? '',
                ($row['equip_modelo'] ?? $row['equip_tipo'] ?? ''),
            ];

            if (!empty($row['numero_os_legado'])) {
                $subtitleParts[] = 'Legado: ' . $row['numero_os_legado'];
            }

            if ($legacyOnly && !empty($row['legacy_origem'])) {
                $subtitleParts[] = 'Origem: ' . $row['legacy_origem'];
            }

            $found[] = [
                'title' => (string) ($row['numero_os'] ?? ''),
                'subtitle' => implode(' - ', array_values(array_filter($subtitleParts, static fn ($part) => trim((string) $part) !== ''))),
                'url' => base_url('os/visualizar/' . $row['id']),
                'icon' => $icon,
                'badge' => $legacyOnly ? 'Legado' : (string) ($row['status'] ?? ''),
            ];
        }

        return $found;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchClientes(string $term): array
    {
        $model = new ClienteModel();
        $rows = $model->groupStart()
                ->like('nome_razao', $term)
                ->orLike('cpf_cnpj', $term)
                ->orLike('telefone1', $term)
                ->orLike('email', $term)
            ->groupEnd()
            ->limit(10)
            ->find();

        $found = [];
        foreach ($rows as $row) {
            $found[] = [
                'title' => (string) $row['nome_razao'],
                'subtitle' => (string) ($row['cpf_cnpj'] ?? $row['email'] ?? $row['telefone1'] ?? ''),
                'url' => base_url('clientes/visualizar/' . $row['id']),
                'icon' => 'bi-person',
                'badge' => 'Cliente',
            ];
        }

        return $found;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchEquipamentos(string $term): array
    {
        $model = new EquipamentoModel();
        $rows = $model->select('equipamentos.*, tipos.nome as tipo_nome, marcas.nome as marca_nome, modelos.nome as modelo_nome, clientes.nome_razao as cliente_nome')
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
        foreach ($rows as $row) {
            $found[] = [
                'title' => trim(($row['marca_nome'] ?? '') . ' ' . ($row['modelo_nome'] ?? '')),
                'subtitle' => 'S/N: ' . ($row['numero_serie'] ?? 'N/I') . ' - Cli: ' . ($row['cliente_nome'] ?? ''),
                'url' => base_url('equipamentos/visualizar/' . $row['id']),
                'icon' => 'bi-laptop',
                'badge' => (string) ($row['tipo_nome'] ?? ''),
            ];
        }

        return $found;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchWhatsapp(string $term): array
    {
        $model = new MensagemWhatsappModel();
        $rows = $model->select('mensagens_whatsapp.*, clientes.nome_razao as cliente_nome')
            ->join('clientes', 'clientes.id = mensagens_whatsapp.cliente_id', 'left')
            ->groupStart()
                ->like('mensagens_whatsapp.mensagem', $term)
                ->orLike('mensagens_whatsapp.telefone', $term)
            ->groupEnd()
            ->orderBy('mensagens_whatsapp.created_at', 'DESC')
            ->limit(10)
            ->find();

        $found = [];
        foreach ($rows as $row) {
            $found[] = [
                'title' => mb_strimwidth((string) ($row['mensagem'] ?? ''), 0, 50, '...'),
                'subtitle' => (($row['cliente_nome'] ?? $row['telefone'] ?? '') . ' - ' . date('d/m/Y H:i', strtotime((string) $row['created_at']))),
                'url' => base_url('atendimento-whatsapp?conversa_id=' . $row['conversa_id']),
                'icon' => 'bi-whatsapp',
                'badge' => 'Mensagem',
            ];
        }

        return $found;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchServicos(string $term): array
    {
        $model = new ServicoModel();
        $rows = $model->searchAtivos($term, 5);

        $found = [];
        foreach ($rows as $row) {
            $found[] = [
                'title' => (string) $row['nome'],
                'subtitle' => 'Valor: R$ ' . number_format((float) ($row['valor'] ?? 0), 2, ',', '.'),
                'url' => base_url('servicos/editar/' . $row['id']),
                'icon' => 'bi-tools',
                'badge' => 'Servico',
            ];
        }

        return $found;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchPecas(string $term): array
    {
        $model = new PecaModel();
        $rows = $model->search($term, 10);

        $found = [];
        foreach ($rows as $row) {
            $found[] = [
                'title' => (string) $row['nome'],
                'subtitle' => 'Estoque: ' . ($row['quantidade_atual'] ?? 0) . ' - Preco Venda: R$ ' . number_format((float) ($row['preco_venda'] ?? 0), 2, ',', '.'),
                'url' => base_url('estoque/editar/' . $row['id']),
                'icon' => 'bi-box-seam',
                'badge' => 'Peca',
            ];
        }

        return $found;
    }
}
