# Estrutura de Pastas (resumo tecnico)

Atualizado em 10/06/2026 para a linha `2.23.29`.

```text
sistema-assistencia/
|-- app/
|   |-- Config/
|   |   |-- LegacyImport.php
|   |   `-- SystemRelease.php
|   |
|   |-- Commands/
|   |   |-- LegacyPreflight.php
|   |   |-- LegacyPrepareTarget.php
|   |   |-- LegacyImport.php
|   |   `-- LegacyReport.php
|   |
|   |-- Controllers/
|   |   |-- Home.php
|   |   |-- Os.php
|   |   |-- Configuracoes.php
|   |   |-- WhatsAppWebhook.php
|   |   |-- Crm.php
|   |   |-- Contatos.php
|   |   |-- Checklists.php
|   |   |-- ConhecimentoTemplates.php
|   |   |-- PacotesServicos.php
|   |   |-- Precificacao.php
|   |   |-- AtendimentoMobile.php
|   |   |-- FinanceiroConfiguracoes.php
|   |   |-- FinanceiroCartoes.php
|   |   `-- CentralMensagens.php
|   |
|   |-- Services/
|   |   |-- OsStatusFlowService.php
|   |   |-- OsPdfService.php
|   |   |-- WhatsAppService.php
|   |   |-- MensageriaService.php
|   |   |-- CrmService.php
|   |   |-- CentralMensagensService.php
|   |   |-- ChatbotService.php
|   |   |-- IntencaoService.php
|   |   |-- MetricasMensageriaService.php
|   |   |-- LegacyRecordNormalizer.php
|   |   |-- LegacyCatalogResolver.php
|   |   |-- LegacyImportService.php
|   |   `-- WhatsApp/
|   |       |-- WhatsAppProviderInterface.php
|   |       |-- BulkMessageProviderInterface.php
|   |       |-- MenuiaProvider.php
|   |       |-- LocalGatewayProvider.php
|   |       |-- WebhookProvider.php
|   |       |-- MetaOfficialProvider.php
|   |       |-- NullProvider.php
|   |       `-- NullBulkProvider.php
|   |
|   |-- Models/
|   |   |-- OsStatusModel.php
|   |   |-- OsStatusTransicaoModel.php
|   |   |-- OsStatusHistoricoModel.php
|   |   |-- MensagemWhatsappModel.php
|   |   |-- WhatsappInboundModel.php
|   |   |-- ConversaWhatsappModel.php
|   |   |-- ContatoModel.php
|   |   |-- ConversaOsModel.php
|   |   |-- ConversaTagModel.php
|   |   |-- RespostaRapidaWhatsappModel.php
|   |   |-- ChatbotIntencaoModel.php
|   |   |-- ChatbotFaqModel.php
|   |   |-- ChatbotFluxoModel.php
|   |   |-- ChatbotLogModel.php
|   |   |-- ChatbotRegraErpModel.php
|   |   |-- MensageriaMetricaDiariaModel.php
|   |   |-- CrmEventoModel.php
|   |   |-- CrmInteracaoModel.php
|   |   |-- CrmFollowupModel.php
|   |   |-- CrmPipelineModel.php
|   |   |-- CrmPipelineEtapaModel.php
|   |   |-- CrmTagModel.php
|   |   |-- LegacyImportAliasModel.php
|   |   |-- LegacyImportRunModel.php
|   |   |-- LegacyImportEventModel.php
|   |   |-- OsDefeitoModel.php
|   |   `-- OsNotaLegadaModel.php
|   |
|   |-- Database/Migrations/
|   |   |-- 2026-03-16-090000_PreCrmFoundation.php
|   |   |-- 2026-03-16-121500_AddMenuiaDirectAndWhatsappEnvios.php
|   |   |-- 2026-03-16-210500_AddLocalGatewayAndMensagensWhatsapp.php
|   |   |-- 2026-03-17-100000_AddLinuxGatewayConfig.php
|   |   |-- 2026-03-17-120000_CreateCrmAndCentralMensagens.php
|   |   |-- 2026-03-17-193000_AddCrmMensagensSeedTagsAutomacoes.php
|   |   |-- 2026-03-17-235500_AddCentralAtendimentoInteligente.php
|   |   |-- 2026-03-20-070500_CreateContatosAndLinkConversas.php
|   |   |-- 2026-03-20-091500_AddContatoLifecycleMarketingFields.php
|   |   |-- 2026-03-20-120500_AddContatoEngajamentoLifecycleWindow.php
|   |   |-- 2026-03-23-031500_AddOsAdvancedFilterIndexes.php
|   |   |-- 2026-03-28-030000_AddLegacyMigrationInfrastructure.php
|   |   |-- 2026-03-28-040000_AddLegacyImportAliases.php
|   |   |-- 2026-03-29-010000_AddLegacyTrackingToOsDetailTables.php
|   |   `-- 2026-03-29-020000_CreateOsNotasLegadasTable.php
|   |
|   `-- Views/
|       |-- public/
|       |   `-- landing_page.php
|       |-- crm/
|       |   |-- timeline.php
|       |   |-- interacoes.php
|       |   |-- followups.php
|       |   |-- pipeline.php
|       |   |-- campanhas.php
|       |   |-- metricas_marketing.php
|       |   `-- clientes_inativos.php
|       |-- financeiro/
|       |   |-- index.php
|       |   |-- cartoes.php
|       |   |-- configuracoes.php
|       |   `-- partials/
|       |-- checklists/
|       |   |-- entrada.php
|       |   `-- placeholder.php
|       |-- conhecimento/
|       |   |-- pdf_templates.php
|       |   `-- whatsapp_templates.php
|       |-- pacotes_servicos/
|       |   |-- index.php
|       |   `-- form.php
|       |-- precificacao/
|       |   |-- index.php
|       |   `-- configuracao.php
|       |-- contatos/
|       |   |-- index.php
|       |   `-- form.php
|       `-- central_mensagens/
|           |-- index.php
|           |-- chatbot.php
|           |-- faq.php
|           |-- respostas_rapidas.php
|           |-- fluxos.php
|           |-- filas.php
|           |-- metricas.php
|           |-- configuracoes.php
|           `-- _menu.php
|
|-- whatsapp-api/
|   |-- server.js
|   |-- package.json
|   |-- ecosystem.config.js
|   |-- install-whatsapp-api.sh
|   |-- .env.example
|   |-- .wwebjs_auth/
|   `-- logs/
|
|-- mobile-app/
|   |-- package.json
|   |-- package-lock.json
|   |-- next.config.mjs
|   |-- scripts/
|   `-- node_modules/
|
|-- scripts/
|   `-- install-vps.sh
|
`-- public/uploads/
    |-- os_documentos/
    |-- acessorios/
    |-- estado_fisico/
    `-- equipamentos_perfil/
```

Atualizacao 08/06/2026 - ciclo de vida operacional de equipamentos:

- `app/Controllers/Equipamentos.php`
  - passou a expor `POST /equipamentos/encerrar/{id}` para encerrar a vida util do equipamento com motivo e observacao;
  - passou a expor `POST /equipamentos/reativar/{id}` para devolver o cadastro ao estado `ativo` quando o bem voltar a operar;
  - bloqueia o encerramento quando existir `OS` aberta/em andamento para o equipamento;
  - impede novos vinculos operacionais com clientes quando o cadastro ja estiver encerrado;
  - agora tambem devolve o HTML do historico de ciclo de vida para manter a timeline sincronizada sem refresh manual;
- `app/Controllers/Os.php`
  - sincroniza o encerramento do equipamento automaticamente quando a OS vinculada for finalizada como `descartado`;
- `app/Services/OsSettlementService.php`
  - reaplica a mesma sincronizacao quando a OS sai de `entregue_pagamento_pendente` e volta ao status final `descartado` apos a quitacao;
- `app/Models/EquipamentoLifecycleHistoricoModel.php`
  - persiste e hidrata a timeline de encerramentos, reativacoes e encerramentos automaticos;
- `app/Models/EquipamentoModel.php`
  - ganhou suporte aos campos `status_operacional`, `motivo_encerramento`, `observacao_encerramento` e `encerrado_em`;
  - `getWithCliente()` passou a incluir `os_abertas_count` para sinalizar quando o encerramento deve ficar bloqueado na interface;
  - `countOpenOsByEquipamentoId()` centraliza a leitura de OS em andamento para a regra de negocio do encerramento;
  - `getByCliente()` passou a filtrar apenas equipamentos ativos nos fluxos operacionais, preservando o equipamento ja selecionado em edicoes historicas;
- `app/Services/EquipamentoProfileService.php`
  - passou a derivar `is_encerrado`, `status_operacional_label`, `motivo_encerramento_label` e `encerrado_em_label`;
- `app/Views/equipamentos/partials/lifecycle_history.php`
  - renderiza a timeline do ciclo de vida na ficha do equipamento e tambem serve de fragmento reutilizado nas respostas AJAX;
- `app/Controllers/Os.php`, `app/Controllers/Orcamentos.php` e `app/Controllers/Api/V1/OrdersController.php`
  - passaram a bloquear novas OS em equipamentos encerrados e a ocultar esses registros das listas operacionais;
- `app/Database/Migrations/2026-06-08-101500_AddLifecycleFieldsToEquipamentos.php`
  - nova migration para persistir o ciclo de vida operacional dos equipamentos;
- `app/Database/Migrations/2026-06-08-120000_CreateEquipamentoLifecycleHistoricoTable.php`
  - nova migration para persistir o historico de encerramento e volta a operacao;
- `app/Views/equipamentos/index.php`, `app/Views/equipamentos/show.php`, `app/Views/clientes/show.php` e `app/Views/os/form.php`
  - UI passa a exibir badges de historico, sinalizar `OS em andamento`, bloquear `Nova OS`, permitir reativacao e sincronizar o encerramento sem refresh manual;
- `public/assets/js/scripts.js`
  - o helper global `confirmarEncerramento()` ganhou fluxo dedicado com `SweetAlert2` para o encerramento de equipamentos e agora valida o bloqueio por OS em andamento antes de abrir a confirmacao;
  - o helper `confirmarReativacao()` passou a liberar o retorno do equipamento para `ativo` sem depender de refresh manual;
  - a UI agora atualiza tambem o bloco de historico do equipamento sem recarregar a pagina.

Atualizacao 06/06/2026 - landing comercial publica:

- `app/Controllers/Home.php`
  - passou a montar a landing publica com dados reais do ERP (`empresa_nome`, `empresa_telefone`, `empresa_email`, `empresa_endereco`, logo e icone);
  - normaliza a URL do app mobile/PWA e monta CTA para WhatsApp quando houver numero configurado;
- `app/Views/public/landing_page.php`
  - nova view comercial publica, responsiva, alinhada ao estado real atual do produto;
  - destaca `OS`, `Orcamentos` com link publico, `WhatsApp OS`, `CRM`, `Financeiro gerencial`, `PWA` e `Coletor de Bancada`;
- `app/Config/Routes.php`
  - passou a expor as rotas publicas `/site` e `/apresentacao` sem alterar a rota raiz de login do ERP.

Atualizacao 06/06/2026 - baixa tecnica da OS, cartoes e cobranca automatica:

- `app/Controllers/Os.php`
  - passou a expor `GET /os/encerramento-meta/{id}` para hidratar o modal de baixa da listagem;
  - passou a processar `POST /os/encerrar-ajax/{id}` com baixa tecnica, recebimentos, lucro estimado e sincronizacao do status final;
  - reforcou o bloqueio de alteracoes financeiras em OS ja concluidas tecnicamente, com aprovacao administrativa quando necessario;
- `app/Controllers/FinanceiroCartoes.php`
  - novo controller para a tela `Financas -> Cartoes e taxas`, incluindo cadastros e simulador de venda liquida;
- `app/Services/OsSettlementService.php`
  - centraliza custo estimado da OS, criacao/sincronizacao do titulo financeiro, taxa de cartao, follow-up de retorno e fila de cobranca automatica;
- `app/Services/FinanceiroCartaoService.php`
  - concentra a simulacao de operadora, bandeira, modalidade, parcelas, taxa e valor liquido;
- `app/Commands/OsCobrancasLifecycle.php`
  - novo comando operacional `php spark os:cobrancas` para processar a regua de cobranca pendente;
- `app/Models/FinanceiroCartaoOperadoraModel.php`
  - cadastro das operadoras de maquininha;
- `app/Models/FinanceiroCartaoBandeiraModel.php`
  - cadastro das bandeiras aceitas;
- `app/Models/FinanceiroCartaoTaxaModel.php`
  - definicao das taxas por modalidade, bandeira e faixa de parcelas;
- `app/Models/FinanceiroMovimentoCartaoModel.php`
  - persistencia do metadado financeiro de recebimentos em cartao;
- `app/Models/OsCobrancaAgendamentoModel.php`
  - fila das tentativas automaticas de cobranca em `1`, `3` e `5` dias;
- `app/Database/Migrations/2026-06-06-120000_CreateOsEncerramentoFinanceiroCartoes.php`
  - adiciona colunas de baixa tecnica na tabela `os`;
  - cria as tabelas de cartoes, taxas, metadados de movimento e cobranca automatica;
- `app/Views/os/index.php`
  - ganhou o modal responsivo `Baixa da OS` com resumo do cliente, equipamento, recebimentos e lucro estimado;
- `app/Views/os/form.php`
  - passou a exibir bloqueio financeiro em OS com baixa tecnica concluida e fluxo de aprovacao administrativa;
- `app/Views/financeiro/cartoes.php`
  - nova tela para operadoras, bandeiras, taxas e simulador;
- `public/assets/js/os-closure-modal.js`
  - controla o modal de baixa na listagem, calcula recebimentos, taxas, saldo pendente e abre SweetAlert2 de confirmacao;
- `app/Config/Routes.php`
  - passou a expor as rotas de baixa da OS e o submenu financeiro de cartoes.

Atualizacao 04/06/2026 - inventario tecnico de desktop/notebook:

- `tools/bench-collector/JovemTech.BenchCollector/`
  - projeto fonte do coletor portatil de bancada em `C#`
  - coleta inventario local via `WMI`, provisiona o agente e envia `check-in` ao ERP
  - aplica fallback `BIOS -> MAC` para o numero de serie
  - aceita `--dry-run` sem contexto de `ERP/OS`, para leitura local pura
  - quando houver `OS`, persiste o arquivo final em `C:\JovemTechBenchCollector\inf_<numero_os>.json`
  - quando nao houver `OS`, usa o fallback `C:\JovemTechBenchCollector\last-snapshot.json`
  - o snapshot base agora inclui `collectedAtUtc`, `collectedAtLocal`, `savedAtUtc` e `savedAtLocal`
- `scripts/agents/publish-bench-collector.ps1`
  - instala um SDK .NET local quando necessario
  - publica o executavel self-contained em `public/assets/agents/bench-collector/win-x64/`
  - gera o pacote `public/assets/agents/JovemTechBenchCollector-win-x64.zip`
- `public/assets/agents/bench-collector/win-x64/`
  - saida publicada do coletor portatil para uso direto na bancada
- `public/assets/agents/JovemTechBenchCollector-win-x64.zip`
  - pacote distribuivel do coletor de bancada sem instalacao
- `public/assets/agents/jovemtec-monitor-agent.ps1`
  - script PowerShell mantido como fallback tecnico para a mesma coleta de inventario
  - envia `bootstrap` e `check-in` para a API interna do ERP
- `app/Services/EquipamentoProfileService.php`
  - regra central para `Desktop OEM`, `Desktop montado`, resumo tecnico e sincronizacao vinda do agente
- `app/Controllers/Api/V1/AgentsController.php`
  - provisiona o agente por OS e recebe os snapshots de inventario
- `app/Controllers/Equipamentos.php`
  - provisiona e executa o coletor local via `GET /equipamentos/bench-collector/coletar-local`
  - enriquece o snapshot local com contexto de `OS digital`, `cliente` e `empresa` quando a coleta parte do ERP local
  - prioriza o `chipset` como valor do campo catalogado `Modelo` durante a importacao mapeada do snapshot
  - remove `JovemTechBenchCollector.exe` e `README.md` da pasta local apos a coleta automatica bem-sucedida
  - expõe a leitura local do snapshot via `GET /equipamentos/bench-collector/snapshot-local`
- `app/Controllers/Api/V1/EquipmentsController.php`
  - expoe o catalogo e o payload atualizado de equipamentos com suporte aos novos campos tecnicos
- `app/Models/MonitorAgentModel.php`
  - persistencia do ultimo estado conhecido do agente
- `app/Services/AgentMonitor/AgentMonitorSchemaService.php`
  - garante a infraestrutura minima do monitor de agentes em instalacoes novas

Atualizacao 05/06/2026 - deduplicacao por serie, MAC e IMEI:

- `app/Services/EquipamentoIdentidadeService.php`
  - normaliza `numero_serie` e `imei` para comparacao sem tracos, espacos ou diferenca de caixa
  - detecta quando o valor informado no campo de serie e um `MAC`
  - identifica conflitos de cadastro e separa cenarios de `mesmo cliente`, `cliente ja vinculado` e `outro cliente`
  - centraliza o vinculo seguro de novos clientes ao mesmo equipamento existente
- `app/Controllers/Equipamentos.php`
  - bloqueia novo cadastro web quando `numero_serie`, `MAC` ou `IMEI` ja apontarem para equipamento existente
  - devolve `duplicate_conflict` no fluxo AJAX da `OS`
  - oferece o endpoint `POST /equipamentos/vincular-existente-ajax` para reaproveitar o equipamento e vincular o cliente atual
- `app/Controllers/Api/V1/EquipmentsController.php`
  - responde `409 EQUIPMENT_DUPLICATE_IDENTIFIER` quando a API tentar criar ou atualizar um equipamento com identificador ja associado a outro cadastro
- `app/Database/Migrations/2026-06-05-120000_AddEquipamentoIdentityIndexes.php`
  - cria indices de apoio em `equipamentos.numero_serie` e `equipamentos.imei`
  - remove duplicidades antigas de pares em `equipamento_clientes`
  - aplica unicidade operacional em `(equipamento_id, cliente_id)` para impedir vinculos repetidos

Atualizacao 05/06/2026 - resumo compacto do equipamento na grade de OS:

- `app/Helpers/sistema_helper.php`
  - adiciona `equipamento_resumo_tecnico_essencial()`, priorizando `gabinete`, `chipset` e `processador`
- `app/Controllers/Os.php`
  - a coluna `Equipamento` da listagem passa a encurtar automaticamente `Desktop montado` quando o resumo tecnico estiver longo demais
  - o texto visivel da grade fica compacto, mas o modal continua abrindo com o nome completo e os detalhes integrais do cadastro

## Camada de mensageria
Fluxo interno:
`Controller -> WhatsAppService -> MensageriaService -> Provider`

## Extensao mobile/PWA (base atual)

Documentacao complementar:

- `documentacao/12-app-mobile-pwa/README.md`

Novos blocos estruturais adicionados sem alterar a Central web existente:

- API interna mobile:
  - `app/Controllers/Api/V1/`
    - `BaseApiController.php`
    - `AuthController.php`
    - `UsersController.php`
    - `ClientsController.php`
    - `OrdersController.php`
    - `ConversationsController.php`
    - `MessagesController.php`
    - `NotificationsController.php`
    - `PushSubscriptionsController.php`
    - `RealtimeController.php`
- filtro de auth de API:
  - `app/Filters/ApiTokenAuthFilter.php`
- services mobile:
  - `app/Services/Mobile/ApiTokenService.php`
  - `app/Services/Mobile/MobilePermissionService.php`
  - `app/Services/Mobile/MobileNotificationService.php`
  - `app/Services/Mobile/WebPushService.php`
- models mobile:
  - `app/Models/MobileApiTokenModel.php`
  - `app/Models/MobilePushSubscriptionModel.php`
  - `app/Models/MobileNotificationModel.php`
  - `app/Models/MobileNotificationTargetModel.php`
  - `app/Models/MobileEventOutboxModel.php`
- migration complementar:
  - `app/Database/Migrations/2026-04-03-010000_CreateMobilePwaInfrastructure.php`
- frontend separado:
  - `mobile-app/` (Next.js PWA)
    - `src/app/*`
    - `src/components/*`
    - `src/lib/*`
    - `public/manifest.webmanifest`
    - `public/sw.js`

## Migracao legada SQL
- configuracao: `app/Config/LegacyImport.php`
- comandos:
  - `php spark legacy:preflight`
  - `php spark legacy:import --execute`
  - `php spark legacy:report`
- servico central:
  - `app/Services/LegacyImportService.php`
- auxiliares:
  - `LegacyRecordNormalizer`
  - `LegacyCatalogResolver`
- auditoria:
  - `legacy_import_aliases`
  - `legacy_import_runs`
  - `legacy_import_events`
- guia tecnico dedicado:
  - `documentacao/03-arquitetura-tecnica/migracao-legado-sql.md`

## Observacao de layout embed
- Novo layout tecnico: `app/Views/layouts/embed.php`
- Uso principal: abrir telas de OS (`nova` e `visualizar`) em modal no dashboard, sem carregar shell completo.

## Controle de versao visual (rodape)
- A versao de release exibida no rodape vem de `app/Config/SystemRelease.php` (`$version`).
- O helper `get_system_version()` (em `app/Helpers/sistema_helper.php`) aplica fallback:
  - primeiro tenta `configuracoes.sistema_versao` (se existir)
  - se nao existir, usa `SystemRelease::$version`.

## Camada global de responsividade
- CSS base: `public/assets/css/design-system/layouts/responsive-layout.css`
- JS base: `public/assets/js/scripts.js` (`initUltraResponsiveLayout`)
- CSS dedicado da listagem de OS: `public/assets/css/design-system/layouts/os-list-layout.css`
- CSS dedicado do modal/formulario de OS: `public/assets/css/design-system/layouts/os-form-layout.css`
- JS dedicado da listagem de OS: `public/assets/js/os-list-filters.js`
- Escopo:
  - prevencao de overflow horizontal global;
  - stack mobile para tabelas comuns via `data-label`;
  - reflow automatico de graficos em resize/orientacao/visualViewport.

Beneficios:
- desacoplamento por provider
- troca de provider sem alterar regra de negocio
- padronizacao de logs operacionais

Providers diretos:
- `menuia`
- `api_whats_local` (ambiente local Windows)
- `api_whats_linux` (producao VPS Linux)
- `webhook` (integracao custom)

Provider de massa (futuro CRM):
- `meta_oficial`

## Camada CRM operacional
Fluxo resumido:
`Eventos da OS + WhatsApp + Chatbot -> CrmService/CentralMensagensService -> timeline, follow-ups, pipeline e metricas`

Objetivo:
- transformar evento operacional em relacionamento rastreavel
- manter cliente/OS/conversa sincronizados na mesma base

## Gateway Node (servico externo interno)
Fluxo:
`ERP (proxy PHP) -> API Node (/status,/qr,/restart,/create-message) -> whatsapp-web.js`

Caracteristicas:
- auth por token (`X-Api-Token`/Bearer)
- CORS/origem restritos por `ERP_ORIGIN`
- rate limit em endpoints sensiveis
- logs em `whatsapp-api/logs/gateway.log`
- sessao persistente em `.wwebjs_auth`
- execucao recomendada com PM2

## Camada de containerizacao Docker/Swarm

Blocos adicionados para deploy em host com Traefik:

- `Dockerfile`
- `.dockerignore`
- `docker/runtime/`
  - `entrypoint.sh`
  - `php.ini`
  - `vhost.conf`
- `docker/swarm/`
  - `contabo-stack.yml`
  - `contabo.env.example`
  - `setup-vemfazer-stack.yml`
  - `setup-vemfazer.env.example`
- `scripts/docker/`
  - `deploy-contabo-swarm.sh`
  - `install-vemfazer-stack.sh`

Responsabilidades:

- `Dockerfile`: build da imagem PHP/Apache do ERP;
- `docker/runtime/entrypoint.sh`: gera `.env`, ajusta permissoes, aguarda banco e executa migrations;
- `docker/swarm/contabo-stack.yml`: define os servicos `app` e `db`, volumes e labels do Traefik;
- `docker/swarm/setup-vemfazer-stack.yml`: variante voltada ao ecossistema `Setup Vem Fazer`, com labels parametrizadas por `STACK_SLUG`;
- `scripts/docker/deploy-contabo-swarm.sh`: padroniza `docker build` + `docker stack deploy`.
- `scripts/docker/install-vemfazer-stack.sh`: clona/atualiza o ERP, grava o env do stack, builda a imagem local e publica a stack no Swarm ja provisionado.
