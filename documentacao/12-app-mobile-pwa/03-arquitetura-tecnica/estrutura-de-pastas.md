# Estrutura de Pastas

## Frontend mobile

- `mobile-app/src/app` - rotas e telas
- `mobile-app/src/components` - componentes reutilizaveis
- `mobile-app/src/lib` - auth, api, push e utilitarios
- `mobile-app/public` - manifest, service worker e icones
- `mobile-app/scripts` - scripts de suporte

## Backend ERP relacionado ao app

- `app/Controllers/Api/V1` - controllers da API mobile
- `app/Filters` - autenticacao e filtros de API
- `app/Models` - models compartilhados e mobile
- `app/Services` - servicos de notificacao, conversa e OS

## Documentacao exclusiva

- `documentacao/12-app-mobile-pwa`

## Regra de organizacao

Cada nova feature mobile deve atualizar:

1. rota frontend;
2. endpoint ou contrato correspondente;
3. documentacao do app;
4. checklist tecnico quando houver midia, push ou fluxo critico.

