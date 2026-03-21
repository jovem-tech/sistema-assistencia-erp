# Correcao: Engajamento temporal configuravel para Contatos e CRM

Data: 20/03/2026

## Contexto

O lifecycle relacional dos contatos estava correto para conversao operacional:
- `lead_novo`
- `lead_qualificado`
- `cliente_convertido`

Mas faltava uma camada de marketing para recencia de relacionamento, sem destruir o historico de conversao.

## Solucao aplicada

Foi implementado um **segundo eixo** para contatos: `engajamento_status`, separado do lifecycle.

Classificacao temporal:
- `ativo`
- `em_risco`
- `inativo`

Base de calculo:
- `COALESCE(ultimo_contato_em, updated_at, created_at)`

Janelas configuraveis:
- `crm_engajamento_ativo_dias` (padrao `30`)
- `crm_engajamento_risco_dias` (padrao `90`)

## Implementacao tecnica

### 1) Banco de dados

Migration nova:
- `2026-03-20-120500_AddContatoEngajamentoLifecycleWindow.php`

Alteracoes:
- adiciona `contatos.engajamento_status`
- adiciona `contatos.engajamento_recalculado_em`
- cria indice `idx_contatos_engajamento_status`
- backfill inicial de engajamento para contatos existentes
- upsert de configuracoes padrao no `configuracoes`

### 2) Regras de dominio (ContatoModel)

Novas capacidades:
- `supportsEngajamentoFields()`
- `normalizeEngajamentoPeriodos(...)`
- `recalculateEngajamentoBulk(...)`

Comportamento:
- lifecycle continua sendo atualizado por vinculo/nome
- novo contato ja entra com engajamento `ativo`
- conversao para cliente tambem reposiciona engajamento para `ativo`

### 3) CRM - Metricas Marketing

Nova acao:
- `POST /crm/metricas-marketing/engajamento`

Tela de metricas:
- bloco de configuracao dos periodos de engajamento
- cards de distribuicao:
  - contatos ativos
  - contatos em risco
  - contatos inativos

### 4) Contatos (agenda)

Lista de contatos:
- novo filtro por engajamento
- nova coluna de engajamento
- exibicao de dias sem interacao e horario de ultimo recalc

Formulario de contato:
- exibe badge de engajamento na area de contexto (modo edicao)

## Resultado de negocio

- Mantem historico de conversao (`cliente_convertido`) sem regressao artificial.
- Permite marketing/CS identificar esfriamento de relacionamento por tempo.
- Viabiliza campanhas de reativacao sem adulterar lifecycle operacional.

## Arquivos tecnicos alterados

- `app/Database/Migrations/2026-03-20-120500_AddContatoEngajamentoLifecycleWindow.php`
- `app/Models/ContatoModel.php`
- `app/Controllers/Crm.php`
- `app/Controllers/Contatos.php`
- `app/Config/Routes.php`
- `app/Views/crm/metricas_marketing.php`
- `app/Views/contatos/index.php`
- `app/Views/contatos/form.php`

