# Modulo: CRM Integrado

Atualizado em 20/03/2026.

## Objetivo
Consolidar relacionamento tecnico/comercial dentro do ERP, reaproveitando os dados operacionais de:
- clientes
- equipamentos
- ordens de servico
- mensageria WhatsApp

## Estrutura funcional

### 1. Timeline
Rota: `/crm/timeline`

Exibe eventos unificados por cliente/OS:
- abertura de OS
- mudanca de status
- mensagens WhatsApp recebidas/enviadas
- eventos de automacao

Tabela base: `crm_eventos`.

### 2. Interacoes
Rota: `/crm/interacoes`

Registro manual e automatico de contato:
- telefone
- WhatsApp
- e-mail
- presencial
- nota interna

Tabela base: `crm_interacoes`.

### 3. Follow-ups
Rota: `/crm/followups`

Gestao de tarefas de acompanhamento:
- pendente
- concluido
- cancelado

Tabela base: `crm_followups`.

### 4. Pipeline Operacional
Rota: `/crm/pipeline`

Pipeline orientado ao ciclo tecnico da OS:
- novo_atendimento
- equipamento_recebido
- em_diagnostico
- aguardando_aprovacao
- em_reparo
- pronto_retirada
- entregue
- pos_atendimento

Tabelas:
- `crm_pipeline`
- `crm_pipeline_etapas`

<a id="campanhas"></a>
### 5. Campanhas
Rota: `/crm/campanhas`

Painel para consolidar:
- automacoes ativas (`crm_automacoes`)
- templates WhatsApp (`whatsapp_templates`)
- segmentacao por tags (`crm_tags` + `crm_tags_cliente`)

<a id="clientes-inativos"></a>
### 6. Clientes Inativos
Rota: `/crm/clientes-inativos`

Permite:
- listar clientes sem OS recente por janela de dias (minimo 30, padrao 180)
- criar follow-up de reativacao direto da listagem

Endpoint de acao:
- `POST /crm/clientes-inativos/followup`

### 7. Timeline CRM na ficha do cliente
Tela: `/clientes/visualizar/{id}`

Bloco "CRM - Relacionamento" exibe:
- resumo de eventos/interacoes/follow-ups pendentes
- timeline unificada (eventos + interacoes + follow-ups)
- conversas WhatsApp vinculadas ao cliente

<a id="metricas-marketing"></a>
### 8. Metricas Marketing
Rota: `/crm/metricas-marketing`

Painel orientado a marketing e growth com layout estilo SaaS:
- cards KPI no topo com leitura imediata:
  - captados
  - qualificados
  - convertidos
  - taxa de conversao
  - conversas
- deltas de tendencia (ultimos 7 dias vs 7 anteriores), quando houver base estatistica
- grafico principal em ApexCharts para:
  - captados
  - qualificados
  - convertidos
  - conversas
- funil visual de conversao (`captados -> qualificados -> convertidos`)
- bloco de insights automaticos para tomada de decisao
- ranking operacional por responsavel (volume, resolucao e pendencias)
- tabelas de ranking e resumo diario em formato premium:
  - badges numericos e faixas de taxa de resolucao/conversao
  - indicador de intensidade diaria para leitura rapida
  - hierarquia visual de auditoria sem competir com o grafico principal
  - responsividade sem barra de rolagem nos blocos principais (ranking e resumo diario)
  - resumo diario reduzido por breakpoint (oculta intensidade em telas menores)
  - modo card responsivo em telas pequenas (stack por linha com `data-label`) para evitar quebra/estouro

Filtros do dashboard:
- periodo (`hoje`, `7d`, `30d`, `90d`, `mes_atual`, `mes_anterior`, `custom`)
- intervalo customizado (`inicio`, `fim`)
- canal (`contatos.origem` e `conversas_whatsapp.canal`)
- responsavel (`conversas_whatsapp.responsavel_id`)
- status de conversa (`conversas_whatsapp.status`)
- tag da conversa (`conversa_tags.tag_id` com base em `crm_tags`)

Desdobramentos:
- origem de leads (`contatos.origem`) em grafico de rosca + tabela
- distribuicao por canais e taxa de resolucao
- segmentacao por tags CRM (`crm_tags` + `crm_tags_cliente`)
- resumo diario compactado (ultimos pontos da serie)

Definicoes do funil:
- `lead_novo`: contato sem cliente e sem qualificacao de nome completo
- `lead_qualificado`: contato sem cliente com nome completo confiavel
- `cliente_convertido`: contato com `cliente_id` preenchido por vinculo operacional (principalmente OS)

Definicoes de engajamento temporal:
- `ativo`: contato com ultima interacao dentro da janela `crm_engajamento_ativo_dias`
- `em_risco`: contato entre `crm_engajamento_ativo_dias + 1` e `crm_engajamento_risco_dias`
- `inativo`: contato acima de `crm_engajamento_risco_dias`

Configuracao de periodos (na propria tela de metricas):
- `POST /crm/metricas-marketing/engajamento`
- persistencia em `configuracoes`:
  - `crm_engajamento_ativo_dias`
  - `crm_engajamento_risco_dias`
- ao salvar, o sistema recalcula em lote o `engajamento_status` da base `contatos`.

Formulas principais:
- `taxa_qualificacao = leads_qualificados / leads_captados * 100`
- `taxa_conversao = leads_convertidos / leads_qualificados * 100`
- `taxa_conversao_captados = leads_convertidos / leads_captados * 100`

## Integracao com OS

Servico principal: `app/Services/CrmService.php`

Pontos chave:
- `registerOsEvent()`: cria evento CRM no contexto da OS
- `syncPipelineFromOs()`: sincroniza etapa de pipeline pela macrofase/status
- `applyStatusAutomation()`: cria eventos e follow-ups automaticos por status e executa regras dinamicas de `chatbot_regras_erp`

Exemplos de automacao ativa:
- `aguardando_autorizacao` -> follow-up em 2 dias
- `entregue_reparado` -> follow-up de pos-atendimento em 7 dias
- `reparado_disponivel_loja` -> template de pronto para retirada + envio de PDF quando regra ativa

## Integracao com WhatsApp

O CRM recebe eventos/interacoes da camada de mensageria via:
- `CentralMensagensService`
- `WhatsAppService`
- `ChatbotService` (intencao + escalonamento para humano)

Camada de qualificacao inicial:
- `Contato` entra primeiro como agenda de relacionamento
- `Cliente` so e consolidado quando existe vinculo operacional (ex.: abertura de OS)

Toda mensagem inbound/outbound relevante gera historico CRM vinculado a cliente e OS quando disponivel.

## Integracao com Central de Atendimento Inteligente
Submodulos da Central (`/atendimento-whatsapp/*`, com alias `/central-mensagens/*`) alimentam CRM com:
- eventos (`crm_eventos`) por mudanca de status da conversa, automacao e escalonamento
- interacoes (`crm_interacoes`) por inbound/outbound
- follow-ups (`crm_followups`) criados por regras de atendimento
- mensagens espelho (`crm_mensagens`) para auditoria de relacionamento

## Evolucao faseada suportada

1. Inbox operacional com vinculo OS/cliente.
2. Contexto completo da conversa em tempo real.
3. Chatbot por intencao + FAQ + fallback humano.
4. Regras ERP -> mensagens/follow-ups/eventos (motor dinamico).
5. Metricas operacionais para gestao de atendimento.

## Persistencia de mensagens CRM

Tabela: `crm_mensagens` (migration `2026-03-17-193000_AddCrmMensagensSeedTagsAutomacoes.php`)

Uso:
- espelho de mensagens de relacionamento no contexto CRM
- registro por direcao (`inbound`/`outbound`)
- vinculo opcional com conversa, cliente e OS
