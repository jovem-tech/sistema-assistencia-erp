# Modulo: Ordens de Servico

## Objetivo
Controlar o ciclo completo de atendimento tecnico:
- recepcao
- diagnostico
- orcamento
- execucao
- qualidade
- encerramento

## Fluxo de status
Base:
- `os_status`
- `os_status_transicoes`
- `os_status_historico`

Campo operacional:
- `os.estado_fluxo` (`em_atendimento`, `em_execucao`, `pausado`, `pronto`, `encerrado`, `cancelado`)

Servico de regra:
- `app/Services/OsStatusFlowService.php`

## Comunicacao WhatsApp integrada

## Camada de mensageria
- `WhatsAppService` (fachada da OS)
- `MensageriaService` (resolucao de provider)
- contratos:
  - `WhatsAppProviderInterface` (direto)
  - `BulkMessageProviderInterface` (massa/futuro)

## Providers diretos
- `MenuiaProvider`
- `LocalGatewayProvider` (gateway local Node.js)
- `WebhookProvider` (customizado)

## Provider de massa (futuro CRM)
- `MetaOfficialProvider` (stub)

## Envio suportado na OS
- texto manual
- template
- PDF anexo
- texto + PDF

## Logs de envio
Tabela principal atual:
- `mensagens_whatsapp`

Compatibilidade:
- `whatsapp_envios`
- `whatsapp_mensagens`

## PDF da OS
Geracao por `OsPdfService`:
- abertura
- orcamento
- laudo
- entrega
- devolucao sem reparo

Persistencia:
- `os_documentos`
- `public/uploads/os_documentos/OS_<numero_os>/`

## Automacao por status
`Os::triggerAutomaticEventsOnStatus()` pode:
- gerar PDF automaticamente
- enviar template WhatsApp automaticamente

## Rotas-chave
- `POST /os/status/{id}`
- `POST /os/whatsapp/{id}`
- `POST /os/pdf/{id}/gerar`

## Modo embed (dashboard e atalhos rapidos)
- `GET /os/nova?embed=1`
- `GET /os/visualizar/{id}?embed=1`
- Em embed, as telas usam `layouts/embed.php` para abrir em modal sem sidebar/navbar.
- Formularios de criacao/edicao preservam `embed=1` para manter o fluxo dentro do modal.

## Integracao com CRM e inbox
- Alteracao de status dispara `CrmService` para registrar eventos/follow-ups no CRM.
- Mensagens da OS passam a refletir em `mensagens_whatsapp` com `conversa_id` quando houver thread.
- Conversas da Central podem ser vinculadas a OS para atendimento contextual sem sair do inbox.
