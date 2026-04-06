# Correcao: Central de Mensagens sem duplicacao visual de mensagens outbound

Data: 31/03/2026  
Release relacionada: `v2.9.7`

## Problema

Na VPS, algumas mensagens outbound apareciam duplicadas na thread da `Central de Mensagens`, mesmo quando o banco `mensagens_whatsapp` possuia apenas um registro para o envio.

O comportamento foi observado especialmente em envios feitos durante a combinacao de:
- stream SSE em tempo real;
- polling incremental de seguranca;
- reconciliacao de mensagem outbound logo apos o envio.

## Causa raiz

O frontend concatenava novas mensagens diretamente em `state.mensagens`:

- mensagens recebidas via `stream`
- mensagens recebidas via `/conversa/{id}/novas`

Como nao havia consolidacao por `id`, a mesma mensagem podia ser empilhada duas vezes na UI.

## Correcao aplicada

Arquivo principal:
- `public/assets/js/central-mensagens.js`

Mudancas:
- criada normalizacao de `id` da mensagem;
- criada chave defensiva de identidade para mensagens ainda sem `id`;
- implementada rotina `mergeMensagens()` para consolidar lista atual + novas mensagens;
- a renderizacao agora normaliza a thread antes de montar o HTML;
- `appendMensagens()` deixou de usar `concat()` puro e passou a mesclar os registros;
- `openConversa()` tambem passa a abrir a thread ja deduplicada.

## Resultado esperado

- uma mensagem outbound enviada uma unica vez aparece uma unica vez na thread;
- se o mesmo payload chegar novamente pelo stream ou polling, a UI atualiza o registro existente;
- o banco continua sendo a fonte da verdade e a thread reflete esse estado sem multiplicacao visual.

## Validacao minima

1. Abrir a conversa na `Central de Mensagens`.
2. Enviar uma mensagem manual.
3. Confirmar que aparece uma unica bolha outbound.
4. Aguardar polling/stream e verificar que a bolha nao se duplica.
5. Reabrir a conversa e validar que o historico continua sem duplicacao.
