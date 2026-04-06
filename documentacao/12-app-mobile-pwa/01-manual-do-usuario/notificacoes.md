# Notificacoes

## Objetivo

Notificar o usuario sobre eventos relevantes mesmo quando o app estiver em segundo plano.

## Eventos principais

- mensagem inbound;
- atualizacao importante de conversa;
- novas OS ou eventos operacionais futuros.

## Requisitos

- permissao do navegador concedida;
- HTTPS valido;
- service worker registrado;
- subscription ativa no backend.

## Diagnostico

O app deve deixar claro quando a notificacao nao pode ser ativada por:

- falta de HTTPS;
- falta de suporte do navegador;
- iPhone fora de modo instalado;
- permissao negada;
- chave VAPID ausente.

Na tela de avisos, o app tambem mostra o estado da conexao WhatsApp vigente no ERP (provedor ativo + saude da conexao), para facilitar diagnostico de notificacoes operacionais ligadas a OS.

## Guia detalhado

- avisos e diagnostico: `avisos-e-diagnostico.md`
