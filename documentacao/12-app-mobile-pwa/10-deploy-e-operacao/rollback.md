# Rollback

Atualizado em 04/04/2026.

## Objetivo

Definir rollback do app mobile/PWA sem impactar o ERP web.

## Camadas

- frontend Next.js/PM2
- service worker e cache
- endpoints mobile no CodeIgniter
- configuracao de subdominio/rewrite

## Procedimento

1. identificar a versao anterior estavel do app
2. restaurar artefato/build anterior do mobile
3. reiniciar processo do app
4. limpar ou versionar cache do service worker se necessario
5. validar login, conversas, OS e notificacoes

## Regras

- rollback do app nao altera a linha de versao do ERP
- qualquer incompatibilidade com endpoints novos deve ser registrada na nota de release
