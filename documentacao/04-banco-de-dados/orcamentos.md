# Banco de Dados - Orcamentos (Fase 1)

## Migration
- `app/Database/Migrations/2026-04-07-230000_CreateOrcamentosModuleInfrastructure.php`
- `app/Database/Migrations/2026-04-08-120000_AddOrcamentoLifecycleIndexes.php`

## Tabelas criadas

## `orcamentos`
Objetivo: cabecalho do orcamento e vinculos de negocio.

Campos principais:
- `id` (PK)
- `numero` (unico)
- `status`
- `origem`
- `cliente_id` (FK)
- `cliente_nome_avulso`
- `telefone_contato`
- `email_contato`
- `os_id` (FK)
- `equipamento_id` (FK)
- `conversa_id`
- `subtotal`, `desconto`, `acrescimo`, `total`
- `validade_dias`, `validade_data`
- `token_publico` (unico), `token_expira_em`
- timestamps de ciclo (`enviado_em`, `aprovado_em`, `rejeitado_em`, `cancelado_em`)

Indices:
- `numero` unico
- `token_publico` unico
- `status + validade_data`
- `status + os_id + validade_data` (fase 3, painel/conversao/lifecycle)
- `cliente_id + created_at`
- `os_id + created_at`
- `conversa_id + created_at`

## `orcamento_itens`
Objetivo: itens detalhados do orcamento.

Campos principais:
- `id` (PK)
- `orcamento_id` (FK)
- `tipo_item`
- `descricao`
- `quantidade`
- `valor_unitario`
- `desconto`
- `acrescimo`
- `total`
- `ordem`

Indices:
- `orcamento_id + ordem`
- `tipo_item + referencia_id`

## `orcamento_status_historico`
Objetivo: trilha de mudanca de status.

Campos principais:
- `id` (PK)
- `orcamento_id` (FK)
- `status_anterior`
- `status_novo`
- `observacao`
- `origem`
- `alterado_por` (FK usuarios)
- `created_at`

Indices:
- `orcamento_id + created_at`
- `status_novo`

## `orcamento_envios`
Objetivo: auditoria de envios por canal (whatsapp/email/pdf/link).

Campos principais:
- `id` (PK)
- `orcamento_id` (FK)
- `canal`
- `destino`
- `mensagem`
- `documento_path` (arquivo PDF vinculado ao envio, quando houver)
- `status`
- `provedor`
- `referencia_externa`
- `enviado_por` (FK usuarios)
- `enviado_em`

Indices:
- `orcamento_id + created_at`
- `canal + status`

Status praticados na fase 2:
- `pendente` (tentativa iniciada)
- `gerado` (PDF gerado com sucesso)
- `enviado` (envio concluido)
- `duplicado` (envio duplicado evitado pelo provider)
- `erro` (falha no canal)

Status praticados no cabecalho `orcamentos` (fase 3):
- `pendente_abertura_os` -> aprovado por cliente sem OS vinculada.
- `convertido` -> orcamento convertido para `os` ou `venda_manual` usando `convertido_tipo`/`convertido_id`.

## `orcamento_aprovacoes`
Objetivo: log de aprovacao/rejeicao via link publico.

Campos principais:
- `id` (PK)
- `orcamento_id` (FK)
- `token_publico`
- `acao`
- `resposta_cliente`
- `ip_origem`
- `user_agent`
- `created_at`

Indices:
- `orcamento_id + created_at`
- `token_publico`
- `acao`

## Tabela integrada: `crm_followups` (impacto fase 3)
Objetivo: follow-up automatico para orcamentos aguardando, vencidos e pendentes de abertura de OS.

Indice adicional (fase 3):
- `idx_crm_followups_origem_evento` em `origem_evento` para deduplicacao rapida dos follow-ups automaticos por orcamento.

## RBAC
A migration tambem cria (se ausente) o modulo `orcamentos` em `modulos` e concede permissoes iniciais em `grupo_permissoes`.
