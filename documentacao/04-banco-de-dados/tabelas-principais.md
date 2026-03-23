# Tabelas Principais do Banco de Dados

Base: `assistencia_tecnica`  
Atualizado em 22/03/2026 (modulo WhatsApp unificado + CRM/Central + contatos + versao no rodape)

## Nucleo operacional
- `clientes`
- `contatos`
- `equipamentos`
- `equipamentos_fotos`
- `os`
- `os_itens`
- `os_fotos`
- `acessorios_os`
- `fotos_acessorios`
- `estado_fisico_equipamento`
- `estado_fisico_fotos`

## Fluxo de OS (pre-CRM)
- `os_status`
- `os_status_transicoes`
- `os_status_historico`

## CRM integrado

### Timeline e interacoes
- `crm_eventos`
- `crm_interacoes`
- `crm_mensagens`

### Acompanhamento e pipeline
- `crm_followups`
- `crm_pipeline`
- `crm_pipeline_etapas`

### Segmentacao/expansao
- `crm_tags`
- `crm_tags_cliente`
- `crm_oportunidades`
- `crm_automacoes`

## Central de Mensagens
- `conversas_whatsapp`
- `conversa_os`
- `conversa_tags`
- `respostas_rapidas_whatsapp`
- `chatbot_intencoes`
- `chatbot_faq`
- `chatbot_fluxos`
- `chatbot_logs`
- `chatbot_regras_erp`
- `mensageria_metricas_diarias`

Campos chave em `conversas_whatsapp`:
- `contato_id`
- `status` (`aberta`, `aguardando`, `resolvida`, `arquivada`)
- `responsavel_id`
- `primeira_mensagem_em`
- `ultima_mensagem_em`
- `nao_lidas`
- `automacao_ativa`
- `aguardando_humano`
- `prioridade`

Campos chave em `contatos`:
- `cliente_id` (nullable)
- `telefone`
- `telefone_normalizado` (unique)
- `whatsapp_nome_perfil`
- `origem`
- `status_relacionamento` (`lead_novo`, `lead_qualificado`, `cliente_convertido`)
- `engajamento_status` (`ativo`, `em_risco`, `inativo`)
- `engajamento_recalculado_em`
- `qualificado_em`
- `convertido_em`
- `ultimo_contato_em`

Indice operacional:
- `idx_contatos_status_relacionamento` (filtro e metricas de funil)
- `idx_contatos_engajamento_status` (segmentacao de recencia/reativacao)

Regra de negocio:
- contato pode existir sem cliente vinculado
- vinculacao em `clientes` ocorre quando ha conversao operacional (ex.: abertura de OS)

## Mensageria WhatsApp

### `mensagens_whatsapp` (log principal)
Campos relevantes:
- `conversa_id`
- `provider`
- `provider_message_id`
- `direcao` (`inbound`/`outbound`)
- `tipo_conteudo` (`texto`, `pdf`, `imagem`, ...)
- `cliente_id`
- `os_id`
- `telefone`
- `tipo_mensagem`
- `mensagem`
- `arquivo`
- `anexo_path`
- `mime_type`
- `status`
- `resposta_api`
- `erro`
- `payload`
- `lida_em`
- `enviada_em`
- `usuario_id`
- `created_at`
- `updated_at`

Observacoes:
- mensagens inbound com anexo podem ser persistidas sem texto (`mensagem = NULL`), mantendo `arquivo` e `mime_type`.
- anexos inbound salvos em `public/uploads/whatsapp/inbound/YYYY/MM`.

### Outras tabelas de mensageria
- `whatsapp_envios` (compatibilidade operacional)
- `whatsapp_mensagens` (legado)
- `whatsapp_templates`
- `whatsapp_inbound`

### Regras dinamicas de automacao
Tabela: `chatbot_regras_erp`

Campos relevantes:
- `evento_origem` (ex.: `os_status_alterado`, `reparado_disponivel_loja`)
- `condicao_json`
- `acao_json`
- `ativo`

Acoes suportadas:
- `template`
- `followup`
- `crm_evento`

## Documentos PDF
- `os_documentos`

Tipos usados:
- `abertura`
- `orcamento`
- `laudo`
- `entrega`
- `devolucao_sem_reparo`

## Configuracoes relevantes (`configuracoes`)

### Provedor direto
- `whatsapp_enabled`
- `whatsapp_direct_provider`
- `whatsapp_bulk_provider`

### Menuia
- `whatsapp_menuia_url`
- `whatsapp_menuia_appkey`
- `whatsapp_menuia_authkey`

### Gateway local (Windows)
- `whatsapp_local_node_url`
- `whatsapp_local_node_token`
- `whatsapp_local_node_origin`
- `whatsapp_local_node_timeout`

### Gateway Linux (VPS)
- `whatsapp_linux_node_url`
- `whatsapp_linux_node_token`
- `whatsapp_linux_node_origin`
- `whatsapp_linux_node_timeout`

### Webhook/inbound
- `whatsapp_webhook_token`
- `whatsapp_webhook_url`
- `whatsapp_webhook_method`
- `whatsapp_webhook_headers`
- `whatsapp_webhook_payload`

### CRM (engajamento temporal)
- `crm_engajamento_ativo_dias`
- `crm_engajamento_risco_dias`

### Versao de release (rodape)
- `sistema_versao` (opcional, override da versao exibida no rodape)

## Migrations relacionadas
- `2026-03-16-090000_PreCrmFoundation.php`
- `2026-03-16-121500_AddMenuiaDirectAndWhatsappEnvios.php`
- `2026-03-16-210500_AddLocalGatewayAndMensagensWhatsapp.php`
- `2026-03-17-100000_AddLinuxGatewayConfig.php`
- `2026-03-17-120000_CreateCrmAndCentralMensagens.php`
- `2026-03-17-193000_AddCrmMensagensSeedTagsAutomacoes.php`
- `2026-03-20-060000_RemoveWhaticketLegacyModule.php`
- `2026-03-20-070500_CreateContatosAndLinkConversas.php`
- `2026-03-20-091500_AddContatoLifecycleMarketingFields.php`
- `2026-03-20-120500_AddContatoEngajamentoLifecycleWindow.php`
