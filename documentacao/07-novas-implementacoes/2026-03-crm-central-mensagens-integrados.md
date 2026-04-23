# 2026-03 - CRM Integrado + Central de Mensagens

## Objetivo da entrega
Implementar a base estrutural de CRM integrado ao ERP, com inbox operacional de WhatsApp e vinculo direto com OS/cliente.

## Entregas realizadas

### Banco de dados
Migration adicionada:
- `app/Database/Migrations/2026-03-17-120000_CreateCrmAndCentralMensagens.php`
- `app/Database/Migrations/2026-03-17-193000_AddCrmMensagensSeedTagsAutomacoes.php`

Novas tabelas:
- `crm_tags`
- `crm_tags_cliente`
- `crm_eventos`
- `crm_interacoes`
- `crm_followups`
- `crm_pipeline_etapas`
- `crm_pipeline`
- `crm_oportunidades`
- `crm_automacoes`
- `crm_mensagens`
- `conversas_whatsapp`
- `conversa_os`
- `conversa_tags`
- `respostas_rapidas_whatsapp`

Patch em tabela existente:
- `mensagens_whatsapp` (colunas de conversa/direcao/tipo_conteudo/anexo/lida_em/enviada_em + indices)

### Backend
Novos controllers:
- `Crm`
- `CentralMensagens`

Novos services:
- `CrmService`
- `CentralMensagensService`

Modelos novos:
- CRM: eventos/interacoes/followups/pipeline/tags
- Conversas: conversa_whatsapp, conversa_os, conversa_tags, respostas_rapidas

Integracoes de fluxo:
- `Os` agora dispara eventos CRM e automacoes de follow-up por status.
- `WhatsAppWebhook` registra inbound na fila e tambem atualiza conversa/thread CRM.
- `WhatsAppService` grava outbound com `conversa_id` e gera interacao/evento CRM.
- `CentralMensagensService` registra espelho de relacionamento em `crm_mensagens`.
- `Clientes::show` agora exibe bloco CRM com resumo, timeline e conversas WhatsApp do cliente.

### Frontend
Telas CRM:
- `/crm/timeline`
- `/crm/interacoes`
- `/crm/followups`
- `/crm/pipeline`
- `/crm/campanhas`
- `/crm/clientes-inativos`

Central de Mensagens:
- `/central-mensagens`
- lista de conversas
- thread com envio texto/PDF
- painel de contexto do cliente/OS
- gestao de contexto da conversa (status/responsavel/tags CRM)
- vinculacao de conversa com OS
- respostas rapidas
- abertura contextual por URL (`conversa_id` e `q`)

Navegacao:
- menu lateral COMERCIAL com submenu CRM
- item dedicado "Central de Mensagens"
- submenu completo da Central:
  - Conversas
  - Chatbot / Automacao
  - Metricas
  - Respostas Rapidas
  - Fluxos de Atendimento
  - FAQ / Base de Conhecimento
  - Filas e Responsaveis
  - Configuracoes

### Evolucao por fases (Central de Atendimento Inteligente)
Fase consolidada nesta entrega:
1. Inbox operacional em 3 colunas (conversa + thread + contexto)
2. Contexto ERP/CRM na conversa com vinculo de OS, tags e responsavel
3. Modulos administrativos de chatbot:
   - intencoes
   - FAQ
   - fluxos
   - regras ERP
4. Modulo de filas operacionais
5. Modulo de metricas operacionais e consolidacao diaria
6. Modulo de configuracoes da Central (SLA, sync, bot, provider)

## Observacoes de arquitetura
- CRM e Central usam a camada de mensageria desacoplada existente.
- Nao ha acoplamento de UI com provider especifico (Menuia/local/linux).
- Base pronta para evolucao de automacoes CRM, atendimento multiatendente e escala 24h.
