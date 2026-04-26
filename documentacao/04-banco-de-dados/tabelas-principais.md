# Tabelas Principais do Banco de Dados

Base: `assistencia_tecnica`  
Atualizado em 26/04/2026 (catalogo estrutural de equipamentos, pacotes de servicos, oferta dinamica de pacote em orcamentos e notificacao web em tempo real)

## Nucleo operacional
- `clientes`
- `contatos`
- `equipamentos`
- `equipamentos_fotos`
- `equipamentos_tipos`
- `equipamentos_marcas`
- `equipamentos_modelos`
- `equipamentos_catalogo_relacoes`
- `os`
- `os_itens`
- `os_fotos`
- `acessorios_os`
- `fotos_acessorios`
- `estado_fisico_equipamento`
- `estado_fisico_fotos`
- `checklist_tipos`
- `checklist_modelos`
- `checklist_itens`
- `checklist_execucoes`
- `checklist_respostas`
- `checklist_fotos`
- `pacotes_servicos`
- `pacotes_servicos_niveis`
- `orcamento_pacote_links` (legado de compatibilidade)
- `pacotes_ofertas`

Observacao sobre transicao de fluxo:
- os dados legados de `estado_fisico_*` seguem preservados por compatibilidade historica;
- o fluxo atual de OS usa `Checklist de Entrada` estruturado (execucoes/respostas/fotos) conforme tipo de equipamento.

## Atualizacoes de schema (08/04/2026)
Migration: `2026-04-08-153500_AddOsItensPendingWorkflowAndCatalogFilters`

Novas colunas:
- `pecas.tipo_equipamento` (`VARCHAR(100)`, nullable)
- `servicos.tipo_equipamento` (`VARCHAR(100)`, nullable)
- `os_itens.servico_id` (`INT`, nullable, FK para `servicos.id`)
- `os_itens.status_item_estoque` (`VARCHAR(40)`, default `disponivel`)
- `os_itens.estoque_reservado` (`TINYINT(1)`, default `0`)
- `os_itens.pendencia_resolvida_em` (`DATETIME`, nullable)
- `os_itens.pendencia_observacao` (`TEXT`, nullable)
- `os_itens.pendencia_fornecedor` (`VARCHAR(120)`, nullable)
- `os_itens.pendencia_valor_compra` (`DECIMAL(10,2)`, nullable)
- `os_itens.pendencia_data_entrada` (`DATE`, nullable)
- `os_itens.pendencia_tipo_aquisicao` (`VARCHAR(60)`, nullable)
- `os_itens.pendencia_destino_despesa` (`VARCHAR(255)`, nullable)

Novos indices:
- `idx_pecas_tipo_equipamento`
- `idx_servicos_tipo_equipamento`
- `os_itens_servico_id_idx`
- `os_itens_status_item_idx`

Nova FK:
- `fk_os_itens_servico` (`os_itens.servico_id -> servicos.id`, `ON DELETE SET NULL`, `ON UPDATE CASCADE`)

Observacao tecnica:
- o fluxo de insert em `os_itens` agora sanitiza IDs de catalogo e evita persistir `peca_id` invalido, prevenindo o erro FK #1452 em `os_itens_ibfk_2`.

## Atualizacoes de schema (08/04/2026) - Orcamentos
Migration: `2026-04-08-223500_AddEquipamentoCatalogSnapshotToOrcamentos`

Novas colunas:
- `orcamentos.equipamento_tipo_id` (`INT`, nullable, FK para `equipamentos_tipos.id`)
- `orcamentos.equipamento_marca_id` (`INT`, nullable, FK para `equipamentos_marcas.id`)
- `orcamentos.equipamento_modelo_id` (`INT`, nullable, FK para `equipamentos_modelos.id`)
- `orcamentos.equipamento_cor` (`VARCHAR(100)`, nullable)
- `orcamentos.equipamento_cor_hex` (`VARCHAR(7)`, nullable)
- `orcamentos.equipamento_cor_rgb` (`VARCHAR(32)`, nullable)

Novos indices:
- `idx_orcamentos_equip_tipo_id`
- `idx_orcamentos_equip_marca_id`
- `idx_orcamentos_equip_modelo_id`

## Atualizacoes de schema (09/04/2026) - Catalogo estrutural de equipamentos
Migration: `2026-04-09-071500_CreateEquipamentosCatalogoRelacoes`

Nova tabela:
- `equipamentos_catalogo_relacoes`
  - `id` (PK)
  - `tipo_id` (`INT`, FK -> `equipamentos_tipos.id`)
  - `marca_id` (`INT`, FK -> `equipamentos_marcas.id`)
  - `modelo_id` (`INT`, FK -> `equipamentos_modelos.id`)
  - `ativo` (`TINYINT(1)`, default `1`)
  - `created_at`, `updated_at`

Indices:
- `idx_equip_catalogo_rel_tipo`
- `idx_equip_catalogo_rel_marca`
- `idx_equip_catalogo_rel_modelo`
- `idx_equip_catalogo_rel_tipo_marca`
- `ux_equip_catalogo_rel_tipo_marca_modelo` (unico em `tipo_id + marca_id + modelo_id`)

Regra de backfill:
- na migracao, a tabela e populada com combinacoes distintas validas encontradas em `equipamentos` (com validacao de consistencia de marca/modelo).

Uso operacional:
- formularios de OS e Orcamento usam essa relacao para montar o filtro encadeado `Tipo -> Marca -> Modelo`;
- quando a relacao ainda nao existir para um par novo, o sistema mantém fallback por marca (legado) e sincroniza a relacao ao salvar.

## Atualizacoes de schema (09/04/2026) - Pacotes de Servicos
Migration: `2026-04-09-101500_CreatePacotesServicosModule`

Novas tabelas:
- `pacotes_servicos`
  - cabecalho do pacote comercial (`nome`, `categoria`, `tipo_equipamento`, `servico_referencia_id`, `descricao`, `metodologia_origem`, `ordem_apresentacao`, `ativo`).
- `pacotes_servicos_niveis`
  - detalhamento dos niveis (`basico`, `completo`, `premium`) com faixa de preco, prazo, garantia, inclusoes e argumento de venda.

Indices:
- `idx_pacotes_categoria`
- `idx_pacotes_tipo_equip`
- `idx_pacotes_ativo`
- `idx_pacotes_niveis_pacote`
- `idx_pacotes_niveis_ativo`
- `ux_pacotes_niveis_pacote_nivel`

FKs:
- `fk_pacotes_niveis_pacote` (`pacotes_servicos_niveis.pacote_servico_id -> pacotes_servicos.id`, `ON DELETE CASCADE`)
- `fk_pacotes_servico_referencia` (`pacotes_servicos.servico_referencia_id -> servicos.id`, `ON DELETE SET NULL`, quando tabela `servicos` estiver disponivel)

Seed inicial:
- a migration preenche pacotes base da metodologia de 3 niveis quando o catalogo estiver vazio.

## Atualizacoes de schema (09/04/2026) - Link profissional de escolha de pacote (legado de compatibilidade)
Migration: `2026-04-09-111500_CreateOrcamentoPacoteLinks`

Tabela legada:
- `orcamento_pacote_links`
  - trilha de envio e escolha de pacote por token publico;
  - status operacional (`ativo`, `enviado`, `escolhido`, `expirado`, `cancelado`, `erro_envio`);
  - dados da escolha (`nivel_escolhido`, `valor_escolhido`, `garantia_dias`, `prazo_estimado`);
  - referencia ao item aplicado (`orcamento_item_id`).

Observacao operacional:
- a tabela segue no banco para historico/compatibilidade, mas novas gravacoes usam `pacotes_ofertas`.

Indices:
- `ux_orc_pacote_link_token`
- `idx_orc_pacote_link_orcamento_status`
- `idx_orc_pacote_link_status_expira`
- `idx_orc_pacote_link_pacote`
- `idx_orc_pacote_link_item`

FKs:
- `fk_orc_pacote_link_orcamento` (`orcamento_pacote_links.orcamento_id -> orcamentos.id`, `ON DELETE CASCADE`)
- `fk_orc_pacote_link_pacote` (`orcamento_pacote_links.pacote_servico_id -> pacotes_servicos.id`, `ON DELETE SET NULL`)
- `fk_orc_pacote_link_item` (`orcamento_pacote_links.orcamento_item_id -> orcamento_itens.id`, `ON DELETE SET NULL`)

## Atualizacoes de schema (09/04/2026) - Oferta dinamica de pacote
Migration: `2026-04-09-121500_CreatePacotesOfertasDinamicas`

Nova tabela:
- `pacotes_ofertas`
  - oferta por identidade (`cliente_id`, `contato_id`, `telefone_destino`);
  - contexto opcional (`os_id`, `equipamento_id`);
  - token publico (`token_publico`) para escolha externa;
  - status operacional (`ativo`, `enviado`, `escolhido`, `aplicado_orcamento`, `expirado`, `cancelado`, `erro_envio`);
  - trilha completa da escolha (`nivel_escolhido`, `valor_escolhido`, prazo/garantia/inclusos);
  - vinculo opcional ao orcamento/item quando aplicado (`orcamento_id`, `orcamento_item_id`).

Indices:
- `ux_pacotes_ofertas_token`
- `idx_pacotes_ofertas_status_expira`
- `idx_pacotes_ofertas_cliente_status`
- `idx_pacotes_ofertas_contato_status`
- `idx_pacotes_ofertas_telefone_status`
- `idx_pacotes_ofertas_orcamento`
- `idx_pacotes_ofertas_pacote`
- `idx_pacotes_ofertas_orc_item`

FKs:
- `fk_pacotes_ofertas_pacote` (`pacotes_ofertas.pacote_servico_id -> pacotes_servicos.id`, `ON DELETE SET NULL`)
- `fk_pacotes_ofertas_cliente` (`pacotes_ofertas.cliente_id -> clientes.id`, `ON DELETE SET NULL`)
- `fk_pacotes_ofertas_contato` (`pacotes_ofertas.contato_id -> contatos.id`, `ON DELETE SET NULL`)
- `fk_pacotes_ofertas_orcamento` (`pacotes_ofertas.orcamento_id -> orcamentos.id`, `ON DELETE SET NULL`)
- `fk_pacotes_ofertas_orc_item` (`pacotes_ofertas.orcamento_item_id -> orcamento_itens.id`, `ON DELETE SET NULL`)
- `fk_pacotes_ofertas_os` (`pacotes_ofertas.os_id -> os.id`, `ON DELETE SET NULL`)
- `fk_pacotes_ofertas_equip` (`pacotes_ofertas.equipamento_id -> equipamentos.id`, `ON DELETE SET NULL`)

Campos legados de rastreabilidade:
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
- `os_itens.observacao` (tambem usado para registrar a origem do total sintetico legado, como `os.total_servicos`, `os.total_produtos` ou `os.subtotal`)
- `os_status_historico.legacy_origem`
- `os_status_historico.legacy_tabela`
- `os_status_historico.legacy_id`
- `os_defeitos.legacy_origem`
- `os_defeitos.legacy_tabela`
- `os_defeitos.legacy_id`

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
- `idx_os_numero_legado` (`os.numero_os_legado`)
- `ux_legacy_alias_source` (`legacy_import_aliases.source_name`, `legacy_import_aliases.source_entity`, `legacy_import_aliases.source_legacy_id`, `legacy_import_aliases.match_key_type`, `legacy_import_aliases.match_key_value`)
- `idx_legacy_alias_match_key` (`legacy_import_aliases.source_name`, `legacy_import_aliases.source_entity`, `legacy_import_aliases.match_key_type`, `legacy_import_aliases.match_key_value`)
- `idx_legacy_alias_target` (`legacy_import_aliases.target_entity`, `legacy_import_aliases.target_id`)
- `idx_os_itens_os_tipo_descricao` (`os_itens.os_id`, `os_itens.tipo`, `os_itens.descricao`)
- `idx_os_itens_tipo_descricao_os_id` (`os_itens.tipo`, `os_itens.descricao`, `os_itens.os_id`)
- `idx_funcionarios_nome` (`funcionarios.nome`)
- `idx_equipamentos_modelos_nome` (`equipamentos_modelos.nome`)
- `idx_equipamentos_marca_id` (`equipamentos.marca_id`)
- `idx_equipamentos_modelo_id` (`equipamentos.modelo_id`)
- `ux_clientes_legacy_source` (`clientes.legacy_origem`, `clientes.legacy_id`)
- `ux_equipamentos_legacy_source` (`equipamentos.legacy_origem`, `equipamentos.legacy_id`)
- `ux_os_legacy_source` (`os.legacy_origem`, `os.legacy_id`)

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

## Migracao legada SQL

Tabelas de auditoria:
- `legacy_import_aliases`
- `legacy_import_runs`
- `legacy_import_events`
- `os_notas_legadas`

Uso:
- `legacy_import_aliases` registra aliases legados de clientes/equipamentos e a chave forte usada na consolidacao (`cpf_cnpj`, `numero_serie`, `imei`)
- `legacy_import_runs` registra cada execucao de `preflight` ou `import`
- `legacy_import_events` registra eventos por entidade (`clientes`, `equipamentos`, `os`, `os_itens`, `os_status_historico`, `os_defeitos`, `os_notas_legadas`) com severidade, acao e payload
- `os_notas_legadas` preserva observacoes livres e anotacoes historicas do sistema antigo que nao entram em um campo estruturado da OS
- `os_itens` tambem pode receber linhas sinteticas com `legacy_tabela`:
  - `os_totais_servico`
  - `os_totais_peca`
  - `os_totais_consolidado`
  para explicar valores de OS legadas que existiam apenas no cabecalho financeiro do ERP antigo

Objetivo:
- permitir reprocessamento auditavel
- medir importados, atualizados, ignorados e erros
- rastrear conflitos e bloqueios encontrados no legado
- manter a carga resiliente quando o legado trouxer clientes sem telefone valido, gravando `clientes.telefone1 = ''` no destino quando necessario
- preservar o maximo possivel do contexto operacional legado sem duplicar a ordem principal

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

## Extensao Mobile/PWA (v2.11.0)

Tabelas complementares (sem duplicar `clientes`, `os`, `conversas_whatsapp` e `mensagens_whatsapp`):

- `mobile_api_tokens`
  - tokens Bearer hash para auth da API mobile (`token_hash`, `expira_em`, `revogado_em`, `ultimo_uso_em`).
- `mobile_push_subscriptions`
  - subscriptions de push por usuario/dispositivo (`endpoint_hash`, chaves `p256dh/auth`, `ativo`).
- `mobile_notifications`
  - inbox de notificacoes por usuario (`tipo_evento`, `titulo`, `corpo`, `rota_destino`, `payload_json`, `lida_em`);
  - agora atende tanto o app mobile/PWA quanto o sino de notificacoes da navbar web;
  - registra, entre outros eventos, a resposta publica do cliente ao orcamento (`orcamento.public_status_changed`).
- `mobile_notification_targets`
  - relaciona notificacao a alvos de dominio (`order`, `conversation`, `client`, `budget`);
  - no fluxo de orcamento publico, a mesma notificacao pode apontar simultaneamente para `budget` e `order`.
- `mobile_event_outbox`
  - fila de eventos para despacho assíncrono (`event_type`, `aggregate_type`, `status`, `tentativas`, `processado_em`).

Indices operacionais novos:

- `ux_mobile_api_tokens_hash`
- `idx_mobile_api_tokens_usuario`
- `ux_mobile_push_endpoint_hash`
- `idx_mobile_push_usuario`
- `idx_mobile_notif_usuario`
- `idx_mobile_notif_lida`
- `ux_mobile_outbox_event_key`
- `idx_mobile_outbox_status`
- `idx_mobile_outbox_disponivel`

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
- `2026-03-28-030000_AddLegacyMigrationInfrastructure.php`
- `2026-03-28-040000_AddLegacyImportAliases.php`
- `2026-04-03-010000_CreateMobilePwaInfrastructure.php`
