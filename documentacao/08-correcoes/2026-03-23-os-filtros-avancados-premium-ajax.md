# 2026-03-23 - OS: filtros avancados premium (AJAX + chips + persistencia + drawer mobile)

## Contexto
A listagem de Ordens de Servico (`/os`) tinha filtro basico e experiencia limitada para operacao com alto volume.

Objetivo desta entrega:
- elevar UX para padrao SaaS moderno;
- manter compatibilidade com CodeIgniter 4 + DataTables server-side;
- garantir filtros combinados sem reload completo da pagina.

## O que foi implementado

### 1) Backend de filtros avancados
Arquivo: `app/Controllers/Os.php`

- Refatorado `index()` para preparar datasets de filtros:
  - status flat + agrupado
  - macrofases
  - tecnicos ativos
  - tipos de servico (`os_itens`)
  - situacoes operacionais
- Refatorado `datatable()` para aplicar filtros combinados com metodos dedicados.
- Novos metodos:
  - `collectListFilters()`
  - `applyListFilters()`
  - `applySituacaoFilter()`
  - normalizadores de lista, data, inteiro e decimal
- Filtros suportados:
  - `q`, `status` (multiplo), `macrofase`, `estado_fluxo`
  - `data_inicio` / `data_fim`
  - `tecnico_id`, `tipo_servico`
  - `valor_min` / `valor_max`
  - `situacao`

### 2) Frontend premium com aplicacao dinamica
Arquivos:
- `app/Views/os/index.php`
- `public/assets/js/os-list-filters.js`
- `public/assets/css/design-system/layouts/os-list-layout.css`
- `public/assets/css/design-system/index.css` (import)

Implementacoes:
- barra horizontal de filtros com busca global e status multiplo;
- botao `Filtros avancados` (expandir/recolher);
- drawer mobile (`Filtrar ordens`);
- aplicacao dos filtros via AJAX (sem reload de pagina);
- debounce de 300ms na busca;
- chips de filtros ativos + remocao individual;
- acao `Limpar todos`;
- persistencia de filtros em URL + localStorage;
- contador de resultados em tempo real;
- overlay de carregamento suave;
- ajuste responsivo agressivo para 320/360/390/430.

### 3) Performance de banco para filtros
Arquivo:
- `app/Database/Migrations/2026-03-23-031500_AddOsAdvancedFilterIndexes.php`

Indices adicionados:
- `idx_os_status`
- `idx_os_estado_fluxo`
- `idx_os_data_abertura`
- `idx_os_tecnico_id`
- `idx_os_valor_final`
- `idx_os_itens_os_tipo_descricao`

Complemento posterior:
- em `24/03/2026`, a release `v2.2.9` adicionou uma segunda etapa de performance para a mesma listagem:
  - builders separados para `recordsTotal`, `recordsFiltered` e pagina atual;
  - filtros de data e valor reescritos para preservar indices;
  - subconsulta `IN` para `tipo_servico`;
  - indices compostos adicionais em `os` e `os_itens`.
- referencia: `documentacao/07-novas-implementacoes/2026-03-24-release-v2.2.9-otimizacao-listagem-os-50k.md`

Complemento posterior 2:
- em `24/03/2026`, a release `v2.2.10` adicionou hardening focado em alto volume real:
  - paginacao por IDs ordenados antes dos joins de apresentacao;
  - busca global `q` reescrita para usar subconsultas indexadas por cliente, equipamento e tecnico;
  - fallback textual em `relato_cliente` apenas quando nao houver match estruturado;
  - indices adicionais de lookup e ordenacao por `cliente_id` / `equipamento_id`.
- referencia: `documentacao/07-novas-implementacoes/2026-03-24-release-v2.2.10-hardening-paginacao-os-q.md`

Complemento posterior 3:
- em `24/03/2026`, a release `v2.2.11` concluiu a validacao de escala:
  - `FULLTEXT` em `os.relato_cliente` para o fallback textual;
  - busca por equipamento refinada para usar apenas o ramo necessario (`marca` ou `modelo`);
  - benchmark sintetico com `50.000` OS e aprovacao nos cenarios principais e de paginacao profunda.
- referencia: `documentacao/07-novas-implementacoes/2026-03-24-release-v2.2.11-validacao-final-os-50k.md`

## Compatibilidade e regras preservadas
- Rotas existentes foram mantidas.
- Permissoes existentes foram mantidas.
- DataTables server-side permaneceu ativo.
- Fluxo de `+ Nova OS` por modal foi preservado (sem redirecionamento externo).

## Validacao recomendada
1. Abrir `/os` em desktop e aplicar filtros combinados.
2. Confirmar atualizacao da tabela sem reload completo.
3. Confirmar chips ativos e remocao por `X`.
4. Confirmar persistencia apos F5.
5. Validar URL compartilhavel com filtros ativos.
6. Testar mobile (320/360) com drawer de filtros.
7. Rodar migration de indices e validar query de listagem.

## Observacao operacional
Para ativar os indices novos em todos os ambientes:
- executar `php spark migrate`.
