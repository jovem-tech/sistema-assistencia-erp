# Tabelas Mobile

## Tabelas complementares

As tabelas exclusivas do app mobile sao complementares. Elas nao substituem tabelas operacionais do ERP.

## Principais tabelas

- `mobile_api_tokens`
- `mobile_push_subscriptions`
- `mobile_notifications`
- `mobile_notification_targets`
- `mobile_event_outbox`

## Objetivo de cada tabela

- `mobile_api_tokens`: autenticacao e expiracao de tokens do app
- `mobile_push_subscriptions`: subscriptions Web Push por dispositivo
- `mobile_notifications`: notificacoes geradas para o app
- `mobile_notification_targets`: roteamento de entrega por usuario/dispositivo
- `mobile_event_outbox`: fila tecnica para eventos assincornos e expansao futura

## Regras

- nao duplicar tabelas de clientes, OS, conversas ou mensagens;
- manter chaves e relacionamentos claros com usuarios e eventos do ERP;
- toda nova tabela mobile deve ser justificada documentalmente.

## Aprofundamento

Para campos e indices detalhados, consultar:

- `campos-e-indices-mobile.md`
