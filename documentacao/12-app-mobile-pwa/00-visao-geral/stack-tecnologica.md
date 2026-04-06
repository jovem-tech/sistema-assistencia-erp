# Stack Tecnologica

## Frontend

- Next.js 14 (App Router)
- React 18
- TypeScript
- CSS global proprio do app
- Cropper.js para recorte de imagens

## Backend

- CodeIgniter 4
- API interna versionada em `/api/v1`
- Services e Models compartilhados com o ERP

## Banco de dados

- MySQL/MariaDB
- mesma base do ERP
- tabelas complementares para mobile quando necessario

## Runtime e deploy

- Node.js 20+
- PM2 para o app em producao
- Nginx como proxy reverso
- HTTPS obrigatorio para instalacao PWA e push real

## Integracoes principais

- autenticacao do ERP
- OS do ERP
- conversas e mensagens do ERP
- push subscriptions e notificacoes mobile

