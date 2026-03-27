# Correcao: Central de Mensagens - envio WhatsApp com provider indisponivel tratado como 503

Data: 25/03/2026

## Problema

Quando o gateway local do WhatsApp ficava offline, o endpoint `POST /atendimento-whatsapp/enviar` respondia `422`.

Isso gerava dois efeitos ruins:
- o console do navegador indicava erro de validacao (`422`) para um problema que era operacional
- o operador via uma mensagem crua de rede, como `Failed to connect to 127.0.0.1 port 3001`, sem orientacao clara

## Ajuste aplicado

### Backend

Arquivo:
- `app/Controllers/CentralMensagens.php`

Mudanca:
- o fluxo de envio agora distingue falha de validacao do usuario de indisponibilidade do provider
- quando o provider/gateway estiver inacessivel, indisponivel por timeout/rede ou mal configurado, a Central responde:
  - HTTP `503`
  - `code = CM_ENVIO_PROVIDER_UNAVAILABLE`
  - mensagem operacional amigavel

Arquivo de apoio:
- `app/Services/WhatsApp/LocalGatewayProvider.php`

Mudanca:
- o provider local passou a devolver `failure_type` para diferenciar:
  - `gateway_unreachable`
  - `gateway_timeout`
  - `gateway_misconfigured`
  - `provider_unavailable`
  - falhas locais de validacao/arquivo

### Frontend

Arquivo:
- `public/assets/js/central-mensagens.js`

Mudanca:
- o SweetAlert2 de envio passa a identificar `503` / `CM_ENVIO_PROVIDER_UNAVAILABLE`
- titulo e orientacao foram ajustados para deixar claro que o problema esta no gateway/provedor, nao na mensagem digitada

## Resultado esperado

- operador nao perde tempo revisando texto da mensagem quando o problema for infraestrutura
- console deixa de indicar `422` em caso de gateway offline e passa a refletir `503`
- diagnostico operacional fica mais coerente com o estado real do provider

## Observacao operacional

Em ambiente Windows/local, a causa mais comum continua sendo o processo Node do gateway parado em `http://127.0.0.1:3001`.
