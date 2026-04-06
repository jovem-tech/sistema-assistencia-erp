# Avisos e Diagnostico

Atualizado em 04/04/2026.

## Objetivo

Descrever a tela `/notificacoes`, usada para leitura de avisos e diagnostico de push.

## O que a tela mostra

- botao para ativar notificacoes
- botao para marcar todas como lidas
- botao para testar notificacao local
- diagnostico de permissao
- diagnostico de HTTPS
- diagnostico de Service Worker
- diagnostico de PushManager
- diagnostico especifico para iPhone
- card de status da conexao WhatsApp ativa no ERP (provedor atual, estado e mensagem de saude)
- lista de avisos gerados pelo backend

## Comportamento iPhone

- o app orienta uso pela Tela de Inicio
- o diagnostico destaca se o iOS parece suportar push
- o pedido de permissao so faz sentido em contexto PWA instalado

## Regras

- avisos sao carregados por `GET /api/v1/notifications`
- o mesmo endpoint traz `whatsapp_connection` para exibir saude do canal WhatsApp diretamente em `Avisos`
- leitura individual usa `PUT /api/v1/notifications/{id}/read`
- leitura em massa usa `PUT /api/v1/notifications/read-all`
- o registro do dispositivo usa `POST /api/v1/notifications/subscriptions`
