# 2026-03 - Fundacao pre-CRM (OS + WhatsApp + PDF)

## Escopo
Implantacao das pendencias estruturais obrigatorias antes do CRM:
1. Consolidacao do fluxo operacional da OS.
2. Integracao de mensageria WhatsApp desacoplada por provedor.
3. Geracao/versionamento de documentos PDF da OS.

## Entregas tecnicas

### Fluxo de status da OS
- Migration `2026-03-16-090000_PreCrmFoundation` criando:
  - `os_status`
  - `os_status_transicoes`
  - `os_status_historico`
- Campo `estado_fluxo` e `status_atualizado_em` em `os`.
- Seed inicial de status, macrofases e transicoes.
- Migracao automatica de status legados para novos codigos.
- Servico `OsStatusFlowService` para:
  - validar transicoes
  - aplicar status
  - calcular estado de fluxo
  - gravar historico

### WhatsApp operacional (direto) + arquitetura para massa
- Camada de abstracao:
  - `WhatsAppProviderInterface` (direto)
  - `BulkMessageProviderInterface` (massa)
  - `WhatsAppService` (fachada)
- Providers:
  - `MenuiaProvider` (atual, canal direto 1:1)
  - `MetaOfficialProvider` (stub futuro, massa/campanhas)
  - `NullProvider` / `NullBulkProvider` (fallback tecnico)

#### Evolucao aplicada em 16/03/2026 (Menuia Appkey/Authkey)
- Migration `2026-03-16-121500_AddMenuiaDirectAndWhatsappEnvios`:
  - criou tabela `whatsapp_envios` (rastreio principal dos envios)
  - adicionou configuracoes:
    - `whatsapp_direct_provider`
    - `whatsapp_bulk_provider`
    - `whatsapp_menuia_url`
    - `whatsapp_menuia_appkey`
    - `whatsapp_menuia_authkey`
    - `whatsapp_test_phone`
  - adicionou templates:
    - `laudo_concluido`
    - `devolucao_sem_reparo`
- Tela `Configuracoes` recebeu:
  - campos `Appkey` e `Authkey` da Menuia
  - botao `Testar conexao`
  - botao `Enviar mensagem de teste`
- `WhatsAppService` passou a registrar:
  - log principal em `whatsapp_envios`
  - compatibilidade em `whatsapp_mensagens`
- `Os::sendWhatsApp` passou a suportar:
  - envio de texto
  - envio de PDF
  - envio combinado texto + PDF

### Webhook inbound
- Endpoint:
  - `POST /webhooks/whatsapp` (token por header/query)
- Tabela:
  - `whatsapp_inbound`

### PDF da OS
- Dependencia instalada: `dompdf/dompdf:^2.0`.
- Servico: `OsPdfService`.
- Tabela de controle: `os_documentos`.
- Templates HTML:
  - `os/pdf/abertura.php`
  - `os/pdf/orcamento.php`
  - `os/pdf/laudo.php`
  - `os/pdf/entrega.php`
  - `os/pdf/devolucao_sem_reparo.php`
- Persistencia fisica:
  - `public/uploads/os_documentos/OS_<numero_os>/`

### Integracao no modulo OS
- Rotas:
  - `POST /os/whatsapp/{id}`
  - `POST /os/pdf/{id}/gerar`
- `Os::show()` exibe:
  - historico de status
  - painel de envio WhatsApp e log
  - envio rapido de cada PDF por WhatsApp
- `Os::updateStatus()` e `Os::store()` disparam automacoes por status:
  - PDF automatico
  - WhatsApp automatico por template

## Homologacao automatizada
- Comando CLI:
  - `C:\xampp\php\php.exe spark precrm:homologar`
- Arquivo:
  - `app/Commands/PreCrmHomologar.php`
- Valida:
  - transicoes de status
  - geracao dos 5 PDFs
  - logs de WhatsApp (`whatsapp_mensagens` + `whatsapp_envios`)
  - inbound webhook (`whatsapp_inbound`)

## Impacto operacional
- Dashboards e filtros passam a enxergar estado macro da OS.
- Comunicacao com cliente fica rastreavel e auditavel.
- PDFs ficam versionados e reaproveitaveis para envio.
- Base tecnica preparada para CRM consumir:
  - eventos da OS
  - historico de comunicacao
  - documentos gerados

## Atualizacao 17/03/2026 - Gateway local profissional
- `whatsapp-api/server.js` refatorado para operacao segura em producao:
  - variaveis de ambiente (`API_TOKEN`, `ERP_ORIGIN`, `PORT`, `WHATSAPP_SESSION_PATH`)
  - rate limit em endpoints sensiveis
  - autenticacao por token
  - validacao de origem
  - respostas padronizadas (`success/status/message/data|error`)
- `whatsapp-api/ecosystem.config.js` adicionado para processo com PM2.
- `MensageriaService` adicionado no ERP para resolver provider direto desacoplado.
- `LocalGatewayProvider` adicionado como provider oficial do gateway local.
- Nova tabela `mensagens_whatsapp` criada como log padrao ERP/CRM.
- Tela de configuracoes recebeu campos do gateway local:
  - URL
  - token
  - origem
  - timeout
- Modal de gerenciamento do gateway mostra metadados operacionais:
  - status
  - conta conectada
  - ultimo ready
  - ultimo erro
