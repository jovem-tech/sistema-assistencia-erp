# Atualizacao da VPS - Release v2.9.3

Data da execucao: 30/03/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Escopo publicado

- hardening do heartbeat de sessao para reduzir contencao com operacoes AJAX
- fechamento antecipado da sessao no endpoint `sessao/heartbeat`
- reducao da escrita de `last_activity` em heartbeats sucessivos
- adiamento do heartbeat quando houver `fetch` ou `$.ajax` same-origin em andamento
- timeout defensivo de 10 segundos no heartbeat do navegador
- atualizacao da documentacao tecnica e administrativa relacionada

## Arquivos publicados na VPS

- `app/Filters/AuthFilter.php`
- `app/Controllers/Sessao.php`
- `public/assets/js/scripts.js`
- `app/Config/SystemRelease.php`
- `documentacao/03-arquitetura-tecnica/fluxo-de-autenticacao.md`
- `documentacao/02-manual-administrador/configuracao-do-sistema.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-03-30-release-v2.9.3-heartbeat-sessao-vps.md`
- `documentacao/README.md`

## Validacao executada

### Sintaxe remota

```bash
php -l app/Filters/AuthFilter.php
php -l app/Controllers/Sessao.php
php -l app/Config/SystemRelease.php
```

Todos sem erro.

### Servicos

- `php8.3-fpm`: `active`
- `nginx`: `active`

### Teste autenticado na VPS

Fluxo validado com cookie de sessao real:

1. login em `http://161.97.93.120/login`
2. acesso autenticado a `/dashboard`
3. chamada autenticada a `/sessao/heartbeat`
4. chamada AJAX autenticada a `/clientes/salvar_ajax`

Resultados:

- `GET /dashboard` -> `200`
- `GET /sessao/heartbeat` -> `200`
- `POST /clientes/salvar_ajax` -> `200`

O teste de `clientes/salvar_ajax` foi executado de forma controlada e o registro alterado durante a verificacao foi restaurado imediatamente para o valor original.

## Conclusao operacional

- a aplicacao publicada na VPS responde corretamente ao heartbeat autenticado
- o endpoint AJAX de cliente tambem respondeu corretamente no mesmo contexto de sessao
- nao houve reproducao do timeout durante a verificacao autenticada apos o hotfix `v2.9.3`
- caso o navegador do operador ainda mostre erro antigo, a orientacao e forcar recarga com `Ctrl+F5` para limpar cache do `scripts.js`
