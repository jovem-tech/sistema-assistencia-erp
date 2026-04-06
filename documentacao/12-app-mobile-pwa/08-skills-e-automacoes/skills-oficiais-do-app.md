# Skills Oficiais do App

## Objetivo

Padronizar futuras alteracoes do app mobile/PWA, inclusive por IA.

## Skills recomendadas

- `mobile-pwa` - visao geral, arquitetura, API, governanca e pontos de entrada do app
- `mobile-pwa-fotos` - camera, galeria, crop, preview, exclusao, miniaturas e cache visual
- `mobile-pwa-os` - abertura de OS, cliente, equipamento, acessorios e fotos de entrada
- `mobile-pwa-release` - versionamento, changelog, compatibilidade e release operacional
- `mobile-pwa-docs` - lista minima de documentos a atualizar por feature e por fluxo
- `mobile-pwa-next-chunk-guard` - prevencao e recuperacao do erro `/_next/static/* 404` em ambiente local
  - inclui kill de processo node preso na porta 3000, limpeza de `.next` e healthcheck automatico do app

## Local oficial

As skills reais do app agora existem no proprio repositorio:

- `.agents/skills/mobile-pwa/`
- `.agents/skills/mobile-pwa-fotos/`
- `.agents/skills/mobile-pwa-os/`
- `.agents/skills/mobile-pwa-release/`
- `.agents/skills/mobile-pwa-docs/`
- `.agents/skills/mobile-pwa-next-chunk-guard/`

## Regras

- cada skill deve apontar fontes oficiais;
- nenhuma skill deve contradizer o design system do app;
- toda nova skill deve nascer documentada e testavel.
- toda feature mobile nova deve consultar a skill principal `mobile-pwa` antes de implementar.
