<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class LegacyImport extends BaseConfig
{
    public string $dbGroup = 'legacy';

    public string $sourceName = 'legado_sql';

    public int $batchSize = 250;

    public bool $allowCatalogAutoCreate = true;

    public bool $writeInitialStatusHistory = true;

    /**
     * Tabelas operacionais limpas antes da migracao real.
     * Preserva usuarios, permissoes, configuracoes e catalogos estruturais.
     *
     * @var string[]
     */
    public array $targetCleanupTables = [
        'conversa_os',
        'crm_tags_cliente',
        'acessorios_os',
        'defeitos_relatados',
        'estado_fisico_fotos',
        'estado_fisico_equipamento',
        'fotos_acessorios',
        'fotos',
        'os_documentos',
        'os_fotos',
        'os_itens',
        'os_notas_legadas',
        'os_status_historico',
        'os_defeitos',
        'os',
        'equipamentos_fotos',
        'equipamento_clientes',
        'equipamentos',
        'legacy_import_aliases',
        'legacy_import_events',
        'legacy_import_runs',
        'clientes',
    ];

    /**
     * Diretorios de upload que devem ser esvaziados antes da migracao real.
     *
     * @var string[]
     */
    public array $targetCleanupPaths = [
        WRITEPATH . '../public/uploads/acessorios',
        WRITEPATH . '../public/uploads/equipamentos',
        WRITEPATH . '../public/uploads/equipamentos_perfil',
        WRITEPATH . '../public/uploads/estado_fisico',
        WRITEPATH . '../public/uploads/os',
        WRITEPATH . '../public/uploads/os_anormalidades',
        WRITEPATH . '../public/uploads/os_documentos',
    ];

    /**
     * Queries-base. Cada uma precisa expor os aliases esperados pelo pipeline.
     *
     * @var array<string, string>
     */
    public array $queries = [
        'clientes' => <<<SQL
SELECT
    c.id AS legacy_id,
    COALESCE(c.tipo_pessoa, CASE WHEN LENGTH(REGEXP_REPLACE(COALESCE(c.cpf, ''), '[^0-9]', '')) = 14 THEN 'juridica' ELSE 'fisica' END) AS tipo_pessoa,
    c.nome AS nome_razao,
    c.cpf AS cpf_cnpj,
    NULL AS rg_ie,
    c.email AS email,
    c.telefone AS telefone1,
    NULL AS telefone2,
    NULL AS nome_contato,
    NULL AS telefone_contato,
    c.cep AS cep,
    c.endereco AS endereco,
    c.numero AS numero,
    c.complemento AS complemento,
    c.bairro AS bairro,
    c.cidade AS cidade,
    c.estado AS uf,
    NULL AS observacoes
FROM clientes c
SQL,
        'equipamentos' => <<<SQL
SELECT
    CONCAT('os-', o.id) AS legacy_id,
    o.cliente AS legacy_cliente_id,
    COALESCE(NULLIF(TRIM(
        CASE
            WHEN o.equipamento REGEXP '^[0-9]+$' THEN le.nome
            ELSE o.equipamento
        END
    ), ''), 'Nao informado') AS tipo_nome,
    COALESCE(NULLIF(TRIM(
        CASE
            WHEN o.marca REGEXP '^[0-9]+$' THEN lm.nome
            ELSE o.marca
        END
    ), ''), 'Nao informado') AS marca_nome,
    COALESCE(NULLIF(TRIM(
        CASE
            WHEN o.modelo REGEXP '^[0-9]+$' THEN COALESCE(lmu.nome, lmo.nome)
            ELSE o.modelo
        END
    ), ''), 'Nao informado') AS modelo_nome,
    o.cor AS cor,
    NULL AS cor_hex,
    NULL AS cor_rgb,
    o.serial AS numero_serie,
    NULL AS imei,
    COALESCE(NULLIF(TRIM(o.senha_ap), ''), NULLIF(TRIM(o.senha), '')) AS senha_acesso,
    NULL AS estado_fisico,
    o.acessorios AS acessorios,
    o.obs AS observacoes
FROM os o
LEFT JOIN equipamentos le ON o.equipamento REGEXP '^[0-9]+$' AND le.id = CAST(o.equipamento AS UNSIGNED)
LEFT JOIN marcas lm ON o.marca REGEXP '^[0-9]+$' AND lm.id = CAST(o.marca AS UNSIGNED)
LEFT JOIN modelos lmo ON o.modelo REGEXP '^[0-9]+$' AND lmo.id = CAST(o.modelo AS UNSIGNED)
LEFT JOIN modelos_unicos lmu ON o.modelo REGEXP '^[0-9]+$' AND lmu.id = CAST(o.modelo AS UNSIGNED)
WHERE o.cliente IS NOT NULL
SQL,
        'os' => <<<SQL
SELECT
    o.id AS legacy_id,
    CAST(o.id AS CHAR) AS numero_os_legado,
    o.cliente AS legacy_cliente_id,
    CONCAT('os-', o.id) AS legacy_equipamento_id,
    o.status AS status_legado,
    o.prioridade AS prioridade,
    COALESCE(NULLIF(TRIM(o.relato), ''), NULLIF(TRIM(o.defeito), '')) AS relato_cliente,
    COALESCE(NULLIF(TRIM(o.laudo), ''), NULLIF(TRIM(orc.laudo), '')) AS diagnostico_tecnico,
    COALESCE(NULLIF(TRIM(o.condicoes), ''), NULLIF(TRIM(orc.condicoes), '')) AS solucao_aplicada,
    o.data AS data_abertura,
    o.data AS data_entrada,
    o.data_entrega AS data_previsao,
    o.data_pronto AS data_conclusao,
    o.data_saida AS data_entrega,
    CASE
        WHEN COALESCE(o.mao_obra, 0) > 0 THEN o.mao_obra
        WHEN COALESCE(o.total_servicos, 0) > 0 THEN o.total_servicos
        ELSE 0
    END AS valor_mao_obra,
    CASE
        WHEN COALESCE(o.total_produtos, 0) > 0 THEN o.total_produtos
        ELSE 0
    END AS valor_pecas,
    CASE
        WHEN COALESCE(o.subtotal, 0) > 0 THEN o.subtotal
        WHEN (COALESCE(o.total_servicos, 0) + COALESCE(o.total_produtos, 0)) > 0 THEN (COALESCE(o.total_servicos, 0) + COALESCE(o.total_produtos, 0))
        ELSE COALESCE(o.valor, 0)
    END AS valor_total,
    o.desconto AS desconto,
    COALESCE(o.valor, 0) AS valor_final,
    o.mao_obra AS legacy_mao_obra_bruta,
    o.total_servicos AS legacy_total_servicos,
    o.total_produtos AS legacy_total_produtos,
    o.subtotal AS legacy_subtotal,
    o.orcamento_aprovado AS orcamento_aprovado,
    orc.data_resposta AS data_aprovacao,
    o.dias_garantia AS garantia_dias,
    NULL AS garantia_validade,
    CASE
        WHEN NULLIF(TRIM(o.obs), '') IS NOT NULL AND NULLIF(TRIM(orc.obs), '') IS NOT NULL AND TRIM(o.obs) <> TRIM(orc.obs)
            THEN CONCAT(TRIM(o.obs), '\n\nObservacao do orcamento legado: ', TRIM(orc.obs))
        ELSE COALESCE(NULLIF(TRIM(o.obs), ''), NULLIF(TRIM(orc.obs), ''))
    END AS observacoes_internas,
    NULLIF(TRIM(orc.observacao_cliente), '') AS observacoes_cliente,
    o.acessorios AS acessorios,
    COALESCE(NULLIF(TRIM(o.forma_pgto), ''), NULLIF(TRIM(orc.forma_pgto), '')) AS forma_pagamento
FROM os o
LEFT JOIN (
    SELECT o1.*
    FROM orcamentos o1
    INNER JOIN (
        SELECT os_id, MAX(id) AS max_id
        FROM orcamentos
        WHERE os_id IS NOT NULL
        GROUP BY os_id
    ) om ON om.max_id = o1.id
) orc ON orc.os_id = o.id
SQL,
        'os_itens' => <<<SQL
SELECT
    CONCAT('orcamento_itens-', oi.id) AS legacy_id,
    CAST(oi.os_id AS CHAR) AS legacy_os_id,
    'orcamento_itens' AS legacy_tabela,
    CASE WHEN oi.tipo = 'produto' THEN 'peca' ELSE 'servico' END AS tipo,
    COALESCE(NULLIF(TRIM(oi.nome), ''), CASE WHEN oi.tipo = 'produto' THEN p.nome ELSE s.nome END, CONCAT('Item legado #', oi.item_id)) AS descricao,
    oi.observacao AS observacao,
    oi.quantidade AS quantidade,
    oi.valor_unitario AS valor_unitario,
    ((COALESCE(oi.valor_unitario, 0) * COALESCE(oi.quantidade, 1)) - COALESCE(oi.desconto, 0)) AS valor_total
FROM orcamento_itens oi
LEFT JOIN produtos p ON oi.tipo = 'produto' AND p.id = oi.item_id
LEFT JOIN servicos s ON oi.tipo = 'servico' AND s.id = oi.item_id
WHERE oi.os_id IS NOT NULL
  AND oi.tipo IN ('produto', 'servico')

UNION ALL

SELECT
    CONCAT('servicos_orc-', so.id) AS legacy_id,
    CAST(so.os AS CHAR) AS legacy_os_id,
    'servicos_orc' AS legacy_tabela,
    'servico' AS tipo,
    COALESCE(NULLIF(TRIM(s.nome), ''), CONCAT('Servico legado #', so.servico)) AS descricao,
    NULL AS observacao,
    so.quantidade AS quantidade,
    so.valor AS valor_unitario,
    COALESCE(so.total, (COALESCE(so.quantidade, 1) * COALESCE(so.valor, 0))) AS valor_total
FROM servicos_orc so
LEFT JOIN servicos s ON s.id = so.servico
WHERE so.os IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM orcamento_itens oi
      WHERE oi.os_id = so.os
        AND oi.tipo IN ('produto', 'servico')
  )

UNION ALL

SELECT
    CONCAT('produtos_orc-', po.id) AS legacy_id,
    CAST(po.os AS CHAR) AS legacy_os_id,
    'produtos_orc' AS legacy_tabela,
    'peca' AS tipo,
    COALESCE(NULLIF(TRIM(p.nome), ''), CONCAT('Peca legada #', po.produto)) AS descricao,
    NULL AS observacao,
    po.quantidade AS quantidade,
    po.valor AS valor_unitario,
    COALESCE(po.total, (COALESCE(po.quantidade, 1) * COALESCE(po.valor, 0))) AS valor_total
FROM produtos_orc po
LEFT JOIN produtos p ON p.id = po.produto
WHERE po.os IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM orcamento_itens oi
      WHERE oi.os_id = po.os
        AND oi.tipo IN ('produto', 'servico')
  )
SQL,
        'os_status_historico' => <<<SQL
SELECT
    CONCAT('historico_status_os-', h.id) AS legacy_id,
    CAST(h.id_os AS CHAR) AS legacy_os_id,
    'historico_status_os' AS legacy_tabela,
    h.status_anterior AS status_anterior,
    h.status_novo AS status_novo,
    h.data_alteracao AS data_alteracao,
    h.id_usuario AS legacy_usuario_id,
    h.observacao AS observacao,
    h.acao_financeira AS acao_financeira,
    h.mensagem_cliente AS mensagem_cliente
FROM historico_status_os h
WHERE h.id_os IS NOT NULL

UNION ALL

SELECT
    CONCAT('os_historico-', h.id) AS legacy_id,
    CAST(h.os_id AS CHAR) AS legacy_os_id,
    'os_historico' AS legacy_tabela,
    h.status_anterior AS status_anterior,
    h.novo_status AS status_novo,
    h.data_alteracao AS data_alteracao,
    h.usuario_id AS legacy_usuario_id,
    h.observacoes AS observacao,
    NULL AS acao_financeira,
    NULL AS mensagem_cliente
FROM os_historico h
WHERE h.os_id IS NOT NULL
SQL,
        'os_defeitos' => <<<SQL
SELECT
    CONCAT('os_defeitos-', d.id) AS legacy_id,
    CAST(d.os_id AS CHAR) AS legacy_os_id,
    'os_defeitos' AS legacy_tabela,
    d.defeito_id AS legacy_defeito_id,
    d.descricao AS descricao,
    d.tipo AS tipo,
    d.criado_em AS created_at
FROM os_defeitos d
WHERE d.os_id IS NOT NULL
SQL,
        'os_notas_legadas' => <<<SQL
SELECT
    CONCAT('os_historicos-', h.id) AS legacy_id,
    CAST(h.os AS CHAR) AS legacy_os_id,
    'os_historicos' AS legacy_tabela,
    h.acao AS titulo,
    h.descricao AS conteudo,
    h.data AS created_at
FROM os_historicos h
WHERE h.os IS NOT NULL
SQL,
    ];

    /**
     * Campos obrigatorios que precisam existir como alias nas consultas-base.
     *
     * @var array<string, string[]>
     */
    public array $requiredAliases = [
        'clientes' => [
            'legacy_id',
            'nome_razao',
            'telefone1',
        ],
        'equipamentos' => [
            'legacy_id',
            'legacy_cliente_id',
            'tipo_nome',
            'marca_nome',
            'modelo_nome',
        ],
        'os' => [
            'legacy_id',
            'numero_os_legado',
            'legacy_cliente_id',
            'legacy_equipamento_id',
            'status_legado',
            'data_abertura',
        ],
        'os_itens' => [
            'legacy_id',
            'legacy_os_id',
            'legacy_tabela',
            'tipo',
            'descricao',
        ],
        'os_status_historico' => [
            'legacy_id',
            'legacy_os_id',
            'legacy_tabela',
            'data_alteracao',
        ],
        'os_defeitos' => [
            'legacy_id',
            'legacy_os_id',
            'legacy_tabela',
            'descricao',
        ],
        'os_notas_legadas' => [
            'legacy_id',
            'legacy_os_id',
            'legacy_tabela',
            'titulo',
            'conteudo',
        ],
    ];

    /**
     * Mapa explicito de status do legado para o fluxo atual.
     *
     * @var array<string, string>
     */
    public array $statusMap = [
        'triagem' => 'triagem',
        'diagnostico' => 'diagnostico',
        'diagnostico_tecnico' => 'diagnostico',
        'aguardando_avaliacao' => 'aguardando_avaliacao',
        'verificacao_garantia' => 'verificacao_garantia',
        'aguardando_orcamento' => 'aguardando_orcamento',
        'aguardando_autorizacao' => 'aguardando_autorizacao',
        'aguardando_reparo' => 'aguardando_reparo',
        'reparo_execucao' => 'reparo_execucao',
        'cumprimento_garantia' => 'cumprimento_garantia',
        'retrabalho' => 'retrabalho',
        'testes_operacionais' => 'testes_operacionais',
        'testes_finais' => 'testes_finais',
        'reparo_concluido' => 'reparo_concluido',
        'reparado_disponivel_loja' => 'reparado_disponivel_loja',
        'garantia_concluida' => 'garantia_concluida',
        'irreparavel' => 'irreparavel',
        'irreparavel_disponivel_loja' => 'irreparavel_disponivel_loja',
        'reparo_recusado' => 'reparo_recusado',
        'entregue_reparado' => 'entregue_reparado',
        'devolvido_sem_reparo' => 'devolvido_sem_reparo',
        'descartado' => 'descartado',
        'cancelado' => 'cancelado',
        'pagamento_pendente' => 'pagamento_pendente',
        'entregue_pagamento_pendente' => 'entregue_pagamento_pendente',
        'aguardando_peca' => 'aguardando_peca',
        'aguardando_analise' => 'triagem',
        'aguardando_aprovacao' => 'aguardando_autorizacao',
        'aprovado' => 'aguardando_reparo',
        'em_reparo' => 'reparo_execucao',
        'pronto' => 'reparado_disponivel_loja',
        'entregue' => 'entregue_reparado',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->sourceName = (string) (env('legacyImport.sourceName', $this->sourceName) ?: $this->sourceName);
        $this->batchSize = max(1, (int) env('legacyImport.batchSize', $this->batchSize));
        $this->allowCatalogAutoCreate = filter_var(env('legacyImport.allowCatalogAutoCreate', $this->allowCatalogAutoCreate), FILTER_VALIDATE_BOOLEAN);
        $this->writeInitialStatusHistory = filter_var(env('legacyImport.writeInitialStatusHistory', $this->writeInitialStatusHistory), FILTER_VALIDATE_BOOLEAN);
    }
}
