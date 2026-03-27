# Tabelas Principais do Banco de Dados

Base: `assistencia_tecnica`  
Atualizado em 26/03/2026 (modulo WhatsApp unificado + CRM/Central + contatos + otimizacoes da listagem de OS + timeout configuravel de sessao + workflow configuravel de status)

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

Indices operacionais para listagem avancada de OS:
- `idx_os_status` (`os.status`)
- `idx_os_estado_fluxo` (`os.estado_fluxo`)
- `idx_os_data_abertura` (`os.data_abertura`)
- `idx_os_tecnico_id` (`os.tecnico_id`)
- `idx_os_valor_final` (`os.valor_final`)
- `idx_os_data_abertura_id` (`os.data_abertura`, `os.id`)
- `idx_os_status_data_abertura_id` (`os.status`, `os.data_abertura`, `os.id`)
- `idx_os_estado_fluxo_data_abertura_id` (`os.estado_fluxo`, `os.data_abertura`, `os.id`)
- `idx_os_tecnico_data_abertura_id` (`os.tecnico_id`, `os.data_abertura`, `os.id`)
- `idx_os_valor_final_id` (`os.valor_final`, `os.id`)
- `idx_os_cliente_data_abertura_id` (`os.cliente_id`, `os.data_abertura`, `os.id`)
- `idx_os_equipamento_data_abertura_id` (`os.equipamento_id`, `os.data_abertura`, `os.id`)
- `idx_os_relato_cliente_fulltext` (`FULLTEXT` em `os.relato_cliente`)
- `idx_os_itens_os_tipo_descricao` (`os_itens.os_id`, `os_itens.tipo`, `os_itens.descricao`)
- `idx_os_itens_tipo_descricao_os_id` (`os_itens.tipo`, `os_itens.descricao`, `os_itens.os_id`)
- `idx_funcionarios_nome` (`funcionarios.nome`)
- `idx_equipamentos_modelos_nome` (`equipamentos_modelos.nome`)
- `idx_equipamentos_marca_id` (`equipamentos.marca_id`)
- `idx_equipamentos_modelo_id` (`equipamentos.modelo_id`)

Objetivo dos compostos:
- sustentar ordenacao por `data_abertura` com paginacao server-side
- reduzir custo de filtros por `status`, `estado_fluxo`, `tecnico_id`, `cliente_id` e `equipamento_id` quando combinados com ordenacao cronologica
- acelerar filtro por `tipo_servico` sem depender de scan amplo em `os_itens`
- sustentar a busca global `q` por catálogos relacionados sem forcar joins amplos na query principal da listagem
- acelerar o fallback textual de `relato_cliente` sem depender de `LIKE '%...%'` como caminho principal

## Fluxo de OS (pre-CRM)
- `os_status`
- `os_status_transicoes`
- `os_status_historico`

Uso operacional atual:
- `os_status` guarda nome, codigo, macrofase, flags (`ativo`, `status_final`, `status_pausa`) e `ordem_fluxo`
- `os_status_transicoes` define os destinos permitidos configurados na tela `Gestao de Conhecimento > Fluxo de Trabalho OS`
- `os_status_historico` continua registrando cada troca de status executada pelo sistema ou pelo usuario

Regra de fallback do workflow:
- se `os_status_transicoes` nao tiver transicoes ativas configuradas, o ERP usa fallback automatico pela `ordem_fluxo`
- nesse modo, cada status ativo pode avancar para o proximo ou retornar para o anterior na sequencia

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

### Sessao e seguranca
- `sessao_inatividade_minutos`

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
- `2026-03-23-031500_AddOsAdvancedFilterIndexes.php`
- `2026-03-24-014500_AddOsListPerformanceIndexes.php`
- `2026-03-24-021500_AddOsSearchLookupIndexes.php`
- `2026-03-24-022500_AddOsLookupOrderingIndexes.php`
- `2026-03-24-023500_DropRedundantEquipamentoMarcaSearchIndex.php`
- `2026-03-24-024500_AddOsRelatoFulltextIndex.php`
