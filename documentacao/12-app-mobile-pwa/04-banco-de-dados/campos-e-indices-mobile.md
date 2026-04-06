# Campos e Indices Mobile

Atualizado em 04/04/2026.

## `mobile_api_tokens`

- `usuario_id`: dono do token
- `token_hash`: hash unico do token
- `token_name`: identificador amigavel do dispositivo
- `scope`: escopo futuro
- `ultimo_uso_em`: telemetria de uso
- `expira_em`: validade
- `revogado_em`: revogacao logica

Indices:

- unico por `token_hash`
- consulta por `usuario_id`
- consulta por expiracao
- consulta por revogacao

## `mobile_push_subscriptions`

- `usuario_id`: dono do dispositivo
- `endpoint_hash`: hash unico do endpoint
- `endpoint`: endpoint real de push
- `chave_p256dh`: chave publica do navegador
- `chave_auth`: chave auth do push
- `user_agent`: diagnostico
- `device_label`: rotulo do dispositivo
- `ativo`: controle logico
- `ultimo_ping_em`: telemetria

Indices:

- unico por `endpoint_hash`
- consulta por `usuario_id`
- consulta por `ativo`

## `mobile_notifications`

- `usuario_id`: usuario destinatario
- `tipo_evento`: classificador funcional
- `titulo`: titulo do aviso
- `corpo`: corpo textual
- `rota_destino`: rota sugerida no app
- `payload_json`: contexto bruto
- `lida_em`: leitura
- `enviada_push_em`: auditoria de push

Indices:

- `usuario_id`
- `tipo_evento`
- `lida_em`
- `created_at`

## `mobile_notification_targets`

- `notification_id`: aviso pai
- `tipo_alvo`: tipo de entidade afetada
- `alvo_id`: id da entidade

Indices:

- `notification_id`
- combinacao `tipo_alvo + alvo_id`

## `mobile_event_outbox`

- `event_key`: idempotencia
- `event_type`: tipo do evento
- `aggregate_type`: agregado de negocio
- `aggregate_id`: entidade principal
- `payload_json`: payload bruto
- `status`: pending, processed ou erro
- `tentativas`: retry count
- `disponivel_em`: agenda de processamento
- `processado_em`: auditoria
- `ultimo_erro`: diagnostico

Indices:

- unico por `event_key`
- `event_type`
- `status`
- `disponivel_em`
- `created_at`
