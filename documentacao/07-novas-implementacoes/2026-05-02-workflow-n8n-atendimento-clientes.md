# Workflow n8n de Atendimento a Clientes

Data: 02/05/2026

## Objetivo

Disponibilizar um workflow importavel no n8n para atendimento inicial de clientes via WhatsApp, reaproveitando a arquitetura oficial do ERP:

- recebimento por webhook no n8n;
- espelhamento imediato para `POST /webhooks/whatsapp`;
- resposta automatica por palavras-chave;
- envio da resposta pelo gateway local em `POST /create-message`.

## Arquivo entregue

- `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp.json`

## Fluxo implementado

1. `Entrada WhatsApp`
   - webhook `POST /whatsapp-atendimento-clientes` no n8n.
2. `Normalizar e Decidir`
   - normaliza telefone;
   - detecta texto;
   - ignora eco de mensagem propria (`from_me`);
   - classifica intencao:
     - saudacao
     - status de OS
     - orcamento
     - humano
     - agendamento
     - midia
     - fallback
3. `Espelhar Inbound no ERP`
   - envia o payload para `POST /webhooks/whatsapp`;
   - preserva a Central de Mensagens, CRM e historico do ERP.
4. `Enviar Resposta no Gateway`
   - responde pelo gateway Node oficial em `POST /create-message`.

## Campos que devem ser ajustados apos importar

No node `Normalizar e Decidir`, editar os valores:

- `erpWebhookUrl`
- `erpWebhookToken`
- `gatewayUrl`
- `gatewayToken`
- `erpOrigin`
- `companyName`

## Contratos usados

### ERP inbound

- rota: `POST /webhooks/whatsapp`
- autenticacao: header `X-Webhook-Token`

Campos principais aceitos:

- `from` / `sender` / `number`
- `message` / `text` / `body`
- `has_media`
- `media_mime_type`
- `media_filename`
- `media_base64`

### Gateway outbound

- rota: `POST /create-message`
- autenticacao:
  - `X-Api-Token`
  - `Authorization: Bearer ...`
  - `X-ERP-Origin`

Payload enviado pelo workflow:

```json
{
  "to": "5511999999999",
  "number": "5511999999999",
  "message": "Texto de resposta"
}
```

## Observacoes operacionais

- o workflow foi desenhado para nao responder automaticamente quando o evento vier com `from_me = true`;
- o espelhamento para o ERP continua acontecendo para manter a Central sincronizada;
- as respostas automaticas sao simples e podem ser evoluidas depois com IA, CRM ou consulta direta de OS/orcamento;
- se o provedor de origem enviar campos em nomes diferentes, basta ampliar a lista de aliases no node `Normalizar e Decidir`.
