# Correcao - Central de Mensagens: inbound multimidia com hidratacao de anexos

Data: 02/04/2026  
Release associada: `v2.10.9`

## Problema observado

- mensagens inbound de audio/video/imagem podiam ser exibidas como `[mensagem sem texto]`;
- no sync de historico do gateway, anexos nao eram entregues ao ERP;
- quando a mensagem ja existia por `provider_message_id`, um payload posterior com midia nao preenchia o anexo no registro existente;
- mensagens de voz (`ptt`) podiam cair com tipo incorreto e nao abrir player de audio.

## Ajuste aplicado

- gateway:
  - sync de historico passou a baixar midias e incluir base64/mime/nome no payload;
- backend ERP:
  - classificacao de `ptt`/`voice` como `audio`;
  - dedupe por `provider_message_id` passou a hidratar `arquivo`, `anexo_path`, `mime_type` e `tipo_conteudo` quando a midia chegar depois;
- frontend:
  - fallback visual de midia em sincronizacao para evitar bolha vazia generica.

## Arquivos alterados

- `whatsapp-api/server.js`
- `app/Services/CentralMensagensService.php`
- `public/assets/js/central-mensagens.js`
- `app/Config/SystemRelease.php`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/README.md`

## Resultado esperado

- recepcao de midias inbound alinhada ao comportamento esperado do WhatsApp Web;
- menor incidência de mensagens vazias em threads com anexos;
- player/renderer correto para audio, video e imagem apos sincronizacao.
