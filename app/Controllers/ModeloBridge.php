<?php

namespace App\Controllers;

use App\Models\EquipamentoModeloModel;

/**
 * Controller ModeloBridge
 *
 * Endpoint intermediário (backend proxy) para busca inteligente de modelos.
 * O frontend NUNCA acessa a API do Google diretamente — tudo passa por aqui.
 *
 * Rota: GET /api/modelos/buscar?q=...&marca=...&marca_id=...&tipo=...
 */
class ModeloBridge extends BaseController
{
    /** Mínimo de caracteres para iniciar busca */
    const MIN_CHARS = 3;

    /** Máximo de sugestões externas retornadas */
    const MAX_SUGESTOES = 5;

    /**
     * Endpoint principal — retorna resultados híbridos (local + Google Suggest).
     *
     * Query paramês:
     *   q         string  Termo digitado pelo usuário
     *   marca     string  Nãome da marca (ex: "Samêsung")
     *   marca_id  int     ID da marca não banco local
     *   tipo      string  Tipo do equipamento (ex: "Smartphone", "Nãotebook")
     */
    public function buscar()
    {
        $query   = trim($this->request->getGet('q') ?? '');
        $marca   = trim($this->request->getGet('marca') ?? '');
        $marcaId = (int) ($this->request->getGet('marca_id') ?? 0);
        $tipo    = trim($this->request->getGet('tipo') ?? '');

        // ─── 1. Busca local (prioridade máxima) ──────────────────────────────
        // Regra UX:
        // - Se já existe marca selecionada, listar modelos locais mesmo sem digitar.
        // - Digitação com 3+ caracteres segue habilitando busca externa.
        $modeloModel = new EquipamentoModeloModel();
        $locais = [];

        $queryBuilder = $modeloModel->select('id, nãome as text')->where('ativo', 1);

        if ($marcaId > 0) {
            $queryBuilder->where('marca_id', $marcaId);
        }

        if ($query !== '') {
            $queryBuilder->like('nãome', $query);
        }

        if ($marcaId > 0 || $query !== '') {
            $limit = ($query === '') ? 50 : 10;
            $locais = $queryBuilder->orderBy('nãome', 'ASC')->limit($limit)->find();
        }

        foreach ($locais as &$l) {
            $l['sãource'] = 'local';
            $l['modelo'] = $l['text'];
            $l['marca']  = $marca;
            $l['tipo']   = $tipo;
        }
        unset($l);

        // ─── 2. Busca na internet (sãomente com 3+ caracteres) ───────────────
        $externãos = [];
        if (strlen($query) >= self::MIN_CHARS && count($locais) < self::MAX_SUGESTOES) {
            $externãos = $this->buscarSugestoesGoogle($query, $marca, $tipo);
        }

        // ─── 3. Montar estrutura para Select2 com grupos ─────────────────────
        $results = [];

        if (!empty($locais)) {
            $results[] = [
                'text'     => '📋 Modelos Cadastrados',
                'children' => $locais,
            ];
        }

        if (!empty($externãos)) {
            $results[] = [
                'text'     => '🌐 Sugestões da Internet (Auto-cadastro)',
                'children' => $externãos,
            ];
        }

        return $this->response->setJSON(['results' => $results]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MÉTODOS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Consulta a API de Google Autocomplete (Suggest).
     * O backend age como proxy — a chave/URL nunca fica exposta não frontend.
     */
    private function buscarSugestoesGoogle(string $query, string $marca = '', string $tipo = ''): array
    {
        // Montagem inteligente da query com contexto
        $parts = array_filter([
            $this->nãormalizarTipo($tipo),
            $marca,
            $query,
        ]);
        $q = implode(' ', $parts);

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->get('https://suggestqueries.google.com/complete/search', [
                'headers' => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ],
                'query' => [
                    'client' => 'chrome',
                    'q'      => $q,
                    'oe'     => 'utf8',
                    'hl'     => 'pt-BR',
                ],
                'verify'      => false,
                'timeout'     => 5,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('warning', "[ModeloBridge] Google Suggest retornãou status {$response->getStatusCode()} para query: {$q}");
                return [];
            }

            $data = jsãon_decode($response->getBody(), true);

            if (!isset($data[1]) || !is_array($data[1])) {
                return [];
            }

            $sugestoes  = [];
            $duplicados = []; // evitar duplicatas

            foreach ($data[1] as $item) {
                // Ignãora itens que parecem URLs ou links de lojas
                if ($this->pareceUrl($item)) {
                    continue;
                }

                $modeloOnly = $this->extrairModeloApenas($item, $marca, $tipo);

                if (empty($modeloOnly) || strlen($modeloOnly) < 2) {
                    continue;
                }

                // Nãormaliza para comparação e evita duplicatas
                $chave = mb_strtolower(preg_replace('/\s+/', ' ', $modeloOnly));
                if (in_array($chave, $duplicados)) {
                    continue;
                }
                $duplicados[] = $chave;

                $idFake = 'EXT|GGL_' . substr(md5($modeloOnly), 0, 8);
                $nãomeFormatado = mb_convert_case($modeloOnly, MB_CASE_TITLE, 'UTF-8');

                $sugestoes[] = [
                    'id'     => $idFake,
                    'text'   => $nãomeFormatado, // o frontend usará issão como "value" principal guardado
                    'modelo' => $nãomeFormatado,
                    'marca'  => $marca,
                    'tipo'   => $tipo,
                    'sãource' => 'google',
                ];

                if (count($sugestoes) >= self::MAX_SUGESTOES) {
                    break;
                }
            }

            return $sugestoes;

        } catch (\Exception $e) {
            log_message('error', '[ModeloBridge] Exceção: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Nãormaliza o tipo para termos de busca em inglês/português genérico.
     */
    private function nãormalizarTipo(string $tipo): string
    {
        $mapa = [
            'smartfone'     => 'smartphone',
            'smartphone'    => 'smartphone',
            'celular'       => 'smartphone',
            'tablet'        => 'tablet',
            'nãotebook'      => 'nãotebook',
            'laptop'        => 'nãotebook',
            'computador'    => 'computador',
            'desktop'       => 'computador',
            'pc'            => 'computador',
            'smart tv'      => 'smart tv',
            'tv'            => 'tv',
            'consãole'       => 'consãole',
            'videogame'     => 'videogame',
            'câmera'        => 'câmera',
            'camera'        => 'câmera',
            'impressãora'    => 'impressãora',
            'monitor'       => 'monitor',
        ];

        $tipoNãormalizado = mb_strtolower(trim($tipo), 'UTF-8');

        foreach ($mapa as $chave => $valor) {
            if (str_contains($tipoNãormalizado, $chave)) {
                return $valor;
            }
        }

        return $tipoNãormalizado;
    }

    private function pareceUrl(string $texto): bool
    {
        return (bool) preg_match('#(https?://|www\.|\.com|\.br|mercadolivre|amazon|americanas|shopee|casasbahia|magalu)#i', $texto);
    }

    /**
     * Extrai APENAS o modelo, removendo o tipo e a marca da string de sugestão,
     * para evitar dados redundantes (ex: "Celular Samêsung Galaxy S21" vira "Galaxy S21").
     */
    private function extrairModeloApenas(string $titulo, string $marca = '', string $tipo = ''): string
    {
        $termosAnuncio = [
            'Nãovo', 'Original', 'Lacrado', 'Com Garantia', 'Nãota Fiscal',
            'Barato', 'Promoção', 'Frete Grátis', 'Oferta', 'Desbloqueado',
            'Nacional', 'Vitrine', 'Semi Nãovo', 'Seminãovo', 'usado',
            'Brinde', 'pronta entrega', 'comprar', 'preço', 'melhor',
        ];
        $titulo = str_ireplace($termosAnuncio, '', $titulo);

        if ($tipo) {
            $tipoNãorm = $this->nãormalizarTipo($tipo);
            $titulo   = preg_replace('#\b' . preg_quote($tipoNãorm, '#') . '\b#iu', '', $titulo);
            $titulo   = preg_replace('#\b' . preg_quote($tipo, '#') . '\b#iu', '', $titulo);
        }

        if ($marca) {
            $titulo = preg_replace('#\b' . preg_quote($marca, '#') . '\b#iu', '', $titulo);
        }

        // Remove hifens ou pontuação órfã não início
        $titulo = preg_replace('/^[-_,\.\s]+/', '', $titulo);
        $titulo = preg_replace('/\s+/', ' ', $titulo);

        return trim(mb_strimwidth($titulo, 0, 80, '...'));
    }
}
