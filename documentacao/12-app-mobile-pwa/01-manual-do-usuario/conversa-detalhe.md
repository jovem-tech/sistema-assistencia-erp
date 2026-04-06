# Conversa - Detalhe

Atualizado em 04/04/2026.

## Objetivo

Descrever o comportamento da tela `/conversas/{id}` no app mobile/PWA.

## O que a tela mostra

- nome do contato, cliente ou telefone no cabecalho
- status atual da conversa
- prioridade atual da conversa
- stream em tempo real
- historico de mensagens inbound e outbound
- campo de resposta rapida

## Regras operacionais

- o app tenta abrir SSE em `GET /api/v1/realtime/stream`
- se o stream falhar, o app usa polling silencioso em `GET /api/v1/messages`
- mensagens de equipe ficam alinhadas a direita
- mensagens do cliente ficam alinhadas a esquerda
- conteudos sem texto aparecem como `[tipo_conteudo]`

## Envio de mensagem

- o envio ocorre por `POST /api/v1/messages`
- o operador digita no composer e toca `Enviar`
- apos sucesso, o app puxa apenas as novas mensagens
- o fluxo nao depende de refresh manual

## Erros e fallback

- erro de carregamento aparece em linha discreta na tela
- erro de envio nao fecha a conversa
- reconexao do stream tenta recuperar mensagens perdidas pelo fallback
