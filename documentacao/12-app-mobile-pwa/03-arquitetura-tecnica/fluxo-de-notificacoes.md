# Fluxo de Notificacoes

Atualizado em 04/04/2026.

## Objetivo

Descrever como os eventos do ERP/mobile viram avisos e push no app.

## Fluxo completo

1. Um evento operacional relevante acontece.
2. O backend cria um registro em `mobile_notifications`.
3. Os alvos sao registrados em `mobile_notification_targets`.
4. Se existir dispositivo ativo, o backend dispara Web Push.
5. O app exibe o aviso localmente em `/notificacoes`.

## Tabelas envolvidas

- `mobile_notifications`
- `mobile_notification_targets`
- `mobile_push_subscriptions`
- `mobile_event_outbox`

## Tipos comuns de evento

- mensagem inbound
- nova OS
- mudanca de status da OS
- eventos futuros de CRM e agenda

## Observacoes

- o app continua funcional mesmo sem push
- a lista de avisos continua sendo a fonte operacional confiavel
