<?php

namespace App\Controllers;

class Documentacao extends BaseController
{
    private string $docsPath;

    public function __construct()
    {
        // Apenas administradores acessam
        if (!session()->get('user_id')) {
            redirect()->to('/login')->send();
            exit;
        }
        // Verifica se é admin ou tem grupo 1 (Administrador)
        // Aceita qualquer grupo com visualizar em configuracoes ou grupo_id == 1
        $grupoId = session()->get('user_grupo_id');
        if ($grupoId != 1 && !can('configuracoes', 'visualizar')) {
            redirect()->to('/dashboard')->with('error', 'Acesso restrito a administradores.')->send();
            exit;
        }

        $this->docsPath = ROOTPATH . 'documentacao';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PÁGINA PRINCIPAL — exibe a wiki
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $tree = $this->buildTree($this->docsPath);

        return view('documentacao/index', [
            'title'    => 'Central de Documentação',
            'tree'     => $tree,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CARREGAR CONTEÚDO DE UM ARQUIVO (AJAX)
    // ─────────────────────────────────────────────────────────────────────────
    public function arquivo()
    {
        $relPath = $this->request->getGet('path');

        if (empty($relPath)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Caminho não informado.']);
        }

        // Segurança: impede path traversal
        $relPath   = ltrim(str_replace(['..', '\\'], ['', '/'], $relPath), '/');
        $fullPath  = $this->docsPath . DIRECTORY_SEPARATOR . $relPath;

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Arquivo não encontrado.']);
        }

        $ext     = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $content = file_get_contents($fullPath);
        $type    = in_array($ext, ['md', 'markdown']) ? 'markdown' : ($ext === 'html' ? 'html' : 'text');

        // Mtime para histórico
        $mtime = date('d/m/Y H:i', filemtime($fullPath));

        return $this->response->setJSON([
            'success'  => true,
            'content'  => $content,
            'type'     => $type,
            'filename' => basename($fullPath),
            'path'     => $relPath,
            'modified' => $mtime,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BUSCA (AJAX) — varre todos os arquivos
    // ─────────────────────────────────────────────────────────────────────────
    public function buscar()
    {
        $query = trim($this->request->getGet('q') ?? '');

        if (strlen($query) < 2) {
            return $this->response->setJSON(['results' => []]);
        }

        $results = [];
        $this->searchInFiles($this->docsPath, $query, $results);

        // Limita a 30 resultados
        $results = array_slice($results, 0, 30);

        return $this->response->setJSON(['results' => $results]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ÁRVORE JSON (AJAX) — para atualização dinâmica se necessário
    // ─────────────────────────────────────────────────────────────────────────
    public function arvore()
    {
        return $this->response->setJSON($this->buildTree($this->docsPath));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTERNOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Constrói array com estrutura de pastas/arquivos recursivamente.
     */
    private function buildTree(string $path, string $base = ''): array
    {
        $items = [];

        if (!is_dir($path)) return $items;

        $entries = scandir($path);
        sort($entries); // ordem alfabética

        // Pastas primeiro
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
            $relPath  = $base ? $base . '/' . $entry : $entry;

            if (is_dir($fullPath)) {
                $items[] = [
                    'type'     => 'folder',
                    'name'     => $this->formatName($entry),
                    'raw_name' => $entry,
                    'path'     => $relPath,
                    'children' => $this->buildTree($fullPath, $relPath),
                ];
            }
        }

        // Arquivos depois
        $validExt = ['md', 'markdown', 'html', 'txt'];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
            $relPath  = $base ? $base . '/' . $entry : $entry;
            $ext      = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

            if (is_file($fullPath) && in_array($ext, $validExt)) {
                $items[] = [
                    'type'     => 'file',
                    'name'     => $this->formatName(pathinfo($entry, PATHINFO_FILENAME)),
                    'raw_name' => $entry,
                    'path'     => $relPath,
                    'ext'      => $ext,
                    'modified' => date('d/m/Y', filemtime($fullPath)),
                ];
            }
        }

        return $items;
    }

    /**
     * Converte nome de arquivo/pasta em nome legível.
     * Ex: "01-manual-do-usuario" → "Manual do Usuário"
     * Ex: "ordens-de-servico.md" → "Ordens de Serviço"
     */
    private function formatName(string $raw): string
    {
        // Remove prefixo numérico (ex: "01-", "2026-03-")
        $name = preg_replace('/^\d{2,4}[-_]/', '', $raw);
        $name = preg_replace('/^\d{2}[-_]/', '', $name); // segundo nível se houver

        // Troca hifens e underscores por espaços
        $name = str_replace(['-', '_'], ' ', $name);

        // Capitaliza
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        // Corrige acentuação comum
        $replacements = [
            'Opcoes' => 'Opções', 'Configuracao' => 'Configuração',
            'Configuracoes' => 'Configurações', 'Permissoes' => 'Permissões',
            'Informacoes' => 'Informações', 'Autenticacao' => 'Autenticação',
            'Implementacoes' => 'Implementações', 'Correcoes' => 'Correções',
            'Integracoes' => 'Integrações', 'Migracoes' => 'Migrações',
            'Banco De Dados' => 'Banco de Dados',
            'Visao Geral' => 'Visão Geral',
            'Novas Implementacoes' => 'Novas Implementações',
        ];
        $name = strtr($name, $replacements);

        return trim($name);
    }

    /**
     * Busca recursiva em arquivos.
     */
    private function searchInFiles(string $dir, string $query, array &$results, string $base = ''): void
    {
        if (!is_dir($dir)) return;

        $entries   = scandir($dir);
        $validExt  = ['md', 'markdown', 'html', 'txt'];
        $queryLow  = mb_strtolower($query);

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;

            $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;
            $relPath  = $base ? $base . '/' . $entry : $entry;

            if (is_dir($fullPath)) {
                $this->searchInFiles($fullPath, $query, $results, $relPath);
                continue;
            }

            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, $validExt)) continue;

            $content    = @file_get_contents($fullPath);
            $contentLow = mb_strtolower($content);
            $titleLow   = mb_strtolower($this->formatName(pathinfo($entry, PATHINFO_FILENAME)));

            if (str_contains($contentLow, $queryLow) || str_contains($titleLow, $queryLow)) {
                // Extrai snippet em torno da palavra encontrada
                $pos     = strpos($contentLow, $queryLow);
                $snippet = '';
                if ($pos !== false) {
                    $start   = max(0, $pos - 60);
                    $snippet = '...' . substr($content, $start, 160) . '...';
                    $snippet = strip_tags(preg_replace('/[#*`>\-]/u', '', $snippet));
                    $snippet = preg_replace('/\s+/', ' ', trim($snippet));
                }

                $results[] = [
                    'title'   => $this->formatName(pathinfo($entry, PATHINFO_FILENAME)),
                    'path'    => $relPath,
                    'snippet' => $snippet,
                    'folder'  => $this->formatName(basename(dirname($relPath))),
                ];
            }
        }
    }
}
