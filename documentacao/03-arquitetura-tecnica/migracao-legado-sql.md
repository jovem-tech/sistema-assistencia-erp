# Arquitetura Tecnica - Migracao Legada SQL

Atualizado em 29/03/2026 para a release `2.9.1`.

## Objetivo
Descrever a arquitetura da importacao legada que traz dados do banco `erp` para o ERP novo com:
- rastreabilidade
- reprocessamento seguro
- deduplicacao controlada
- backfill dos detalhes da OS

## Componentes principais

### Configuracao
- `app/Config/Database.php`
  - grupo secundario `legacy`
- `app/Config/LegacyImport.php`
  - queries-base
  - aliases obrigatorios
  - mapa de status
  - parametros operacionais

### Servicos
- `app/Services/LegacyImportService.php`
  - coordena preflight, importacao, backfill e relatorios
- `app/Services/LegacyRecordNormalizer.php`
  - normaliza strings, datas, documentos, telefones, valores e prioridade
- `app/Services/LegacyCatalogResolver.php`
  - resolve/cria `tipo`, `marca` e `modelo`

### Models
- `app/Models/LegacyImportAliasModel.php`
- `app/Models/LegacyImportRunModel.php`
- `app/Models/LegacyImportEventModel.php`
- `app/Models/OsDefeitoModel.php`
- `app/Models/OsNotaLegadaModel.php`

### Commands
- `app/Commands/LegacyPreflight.php`
- `app/Commands/LegacyPrepareTarget.php`
- `app/Commands/LegacyImport.php`
- `app/Commands/LegacyReport.php`

### Persistencia de destino
- `clientes`
- `equipamentos`
- `os`
- `os_itens`
- `os_status_historico`
- `os_defeitos`
- `os_notas_legadas`
- `legacy_import_aliases`
- `legacy_import_runs`
- `legacy_import_events`

## Fluxo do pipeline

```text
Banco legado (grupo legacy)
    -> LegacyImportService::runPreflight()
        -> valida conexao
        -> valida aliases das queries
        -> inspeciona clientes
        -> inspeciona equipamentos
        -> inspeciona OS
        -> inspeciona itens/servicos/pecas
        -> inspeciona historico estruturado
        -> inspeciona defeitos
        -> inspeciona notas legadas
        -> grava resumo/eventos

Banco legado (grupo legacy)
    -> LegacyImportService::runImport()
        -> executa novo preflight
        -> importa clientes
        -> importa equipamentos
        -> importa OS
        -> importa itens/servicos/pecas
        -> importa historico estruturado
        -> importa defeitos
        -> importa notas legadas
        -> executa backfill sintetico de itens quando necessario
        -> grava resumo/eventos
```

## Estrategia de importacao

### Ordem obrigatoria
1. clientes
2. equipamentos
3. ordens de servico
4. itens/servicos/pecas
5. historico estruturado de status
6. defeitos
7. notas legadas

### Adaptacao especifica para o banco `erp`
- `clientes` vem diretamente da tabela `clientes`
- `equipamentos` sao derivados da tabela `os`, porque o legado nao possui entidade compativel de equipamento por cliente
- o `legacy_id` do equipamento derivado e `os-{id_legado}`
- a `os` importada aponta para esse snapshot derivado por `legacy_equipamento_id`
- `orcamentos` complementa a OS com laudo, observacoes, aprovacao e forma de pagamento
- `orcamento_itens` e a fonte prioritaria de composicao
- `servicos_orc` e `produtos_orc` entram como fallback quando a OS nao possui `orcamento_itens`
- `historico_status_os` e `os_historico` alimentam o historico de status quando houver mapeamento de fluxo
- `os_historicos` preserva notas antigas nao estruturadas em `os_notas_legadas`

### Itens sinteticos seguros
Quando o legado nao possuir discriminacao detalhada por item, o pipeline cria linhas sinteticas em `os_itens` para representar:
- total de servicos
- total de pecas
- total consolidado sem classificacao quando a origem so possuir subtotal/valor fechado

Essas linhas usam `legacy_tabela` especifica e existem para evitar OS com financeiro importado, mas composicao vazia.

Prioridade de origem financeira no backfill:
- servicos:
  - `os.mao_obra`
  - fallback para `os.total_servicos`
- pecas:
  - `os.total_produtos`
- consolidado:
  - `os.subtotal`
  - fallback para `os.valor + desconto`

Cada item sintetico grava em `os_itens.observacao` qual campo legado originou o valor consolidado.

### Idempotencia
- cada entidade usa `legacy_origem + legacy_id` como chave de reprocessamento
- se o registro ja existe com essa chave, o pipeline atualiza
- se existe conflito no destino sem marcador legado, a linha e ignorada com evento `skipped_conflict`
- clientes e equipamentos podem convergir por alias seguro quando houver identificador forte

### Nao mesclar por nome
- nao existe heuristica por nome, email ou telefone para unir registros automaticamente
- isso vale para clientes e equipamentos

## Contrato de dados de origem

As queries-base sao configuradas em `app/Config/LegacyImport.php`.

Aliases obrigatorios:
- `clientes`: `legacy_id`, `nome_razao`, `telefone1`
- `equipamentos`: `legacy_id`, `legacy_cliente_id`, `tipo_nome`, `marca_nome`, `modelo_nome`
- `os`: `legacy_id`, `numero_os_legado`, `legacy_cliente_id`, `legacy_equipamento_id`, `status_legado`, `data_abertura`
- `os_itens`: `legacy_id`, `legacy_os_id`, `tipo`, `descricao`
- `os_status_historico`: `legacy_id`, `legacy_os_id`, `status_novo`
- `os_defeitos`: `legacy_id`, `legacy_os_id`, `descricao`
- `os_notas_legadas`: `legacy_id`, `legacy_os_id`, `conteudo`

## Mapeamento de status

O pipeline usa mapa explicito em `LegacyImport::$statusMap`.

Regras:
- status desconhecido bloqueia a carga
- nao existe fallback por similaridade
- historicos legados sem correspondencia clara de status podem ser preservados apenas como nota

## Catalogos

O resolver de catalogos trabalha com normalizacao antes de procurar/criar:
- tipo
- marca
- modelo

Quando `allowCatalogAutoCreate = true`:
- cria `equipamentos_tipos`
- cria `equipamentos_marcas`
- cria `equipamentos_modelos`

Quando `allowCatalogAutoCreate = false`:
- ausencia de catalogo vira bloqueio

## Rastreamento e auditoria

### Campos nas tabelas operacionais
- `clientes.legacy_origem`
- `clientes.legacy_id`
- `equipamentos.legacy_origem`
- `equipamentos.legacy_id`
- `os.legacy_origem`
- `os.legacy_id`
- `os.numero_os_legado`
- `os_itens.legacy_origem`
- `os_itens.legacy_tabela`
- `os_itens.legacy_id`
- `os_status_historico.legacy_origem`
- `os_status_historico.legacy_tabela`
- `os_status_historico.legacy_id`
- `os_defeitos.legacy_origem`
- `os_defeitos.legacy_tabela`
- `os_defeitos.legacy_id`

### Tabelas de auditoria
- `legacy_import_aliases`
- `legacy_import_runs`
- `legacy_import_events`
- `os_notas_legadas`

## Numeracao da OS

Regra arquitetural:
- `numero_os` continua pertencendo ao ERP novo
- `numero_os_legado` preserva a referencia do sistema antigo

Integracao de leitura:
- listagem `/os` aceita busca por `numero_os_legado`
- visualizacao da OS mostra `Numero legado` e `Origem`

## Limpeza controlada da base de destino

Antes da carga real, o fluxo pode limpar os dados operacionais ficticios do ERP novo:
- preview: `php spark legacy:prepare-target`
- execucao: `php spark legacy:prepare-target --execute`
- importacao em um passo: `php spark legacy:import --execute --wipe-target`

Escopo da limpeza:
- clientes, equipamentos, OS e tabelas diretamente relacionadas
- uploads de acessorios, equipamentos, estado fisico, fotos de entrada e documentos da OS

Preservado:
- usuarios
- permissoes
- configuracoes
- catalogos estruturais

## Limites atuais

Ainda nao sao migrados:
- fotos antigas
- anexos binarios
- PDFs antigos
- mensagens/conversas WhatsApp do legado

## Ajuda contextual

Mapeamento adicionado:
- `openDocPage('legacy-migration-architecture')`
