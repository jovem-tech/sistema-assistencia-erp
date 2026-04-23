# Atualizacao completa da VPS - Release v2.5.9

Data da execucao: 27/03/2026  
Ambiente: Producao (`161.97.93.120`)  
Branch/commit implantado: `main` @ `c2f1fb9`

## Escopo publicado

- pacote completo de evolucoes do modulo OS (performance 50k+, workflow, responsividade e UX)
- hardening de sessao e timeout configuravel
- melhorias de central de mensagens/whatsapp
- atualizacao da tela de login com exibicao de versao atual
- atualizacao ampla da documentacao tecnica, funcional e de release

## Procedimento executado na VPS

```bash
cd /var/www/sistema-hml
git fetch --all --tags
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php spark migrate --all --no-header
php spark cache:clear
systemctl restart php8.3-fpm
systemctl restart nginx
```

## Resultado das migracoes

Migracoes aplicadas com sucesso:

1. `2026-03-23-031500_AddOsAdvancedFilterIndexes`
2. `2026-03-24-014500_AddOsListPerformanceIndexes`
3. `2026-03-24-021500_AddOsSearchLookupIndexes`
4. `2026-03-24-022500_AddOsLookupOrderingIndexes`
5. `2026-03-24-023500_DropRedundantEquipamentoMarcaSearchIndex`
6. `2026-03-24-024500_AddOsRelatoFulltextIndex`

## Validacao operacional pos-deploy

- `php8.3-fpm`: `active`
- `nginx`: `active`
- Health check HTTP por GET:
  - `GET /` -> `200`
  - `GET /login` -> `200`

Observacao:
- `curl -I` (HEAD) retornou `404` neste ambiente para rotas de app, por isso a validacao oficial do runbook foi feita com `GET` e codigo HTTP.

## Checklist de fechamento

- [x] Codigo publicado em `main` no GitHub
- [x] VPS atualizada com `git pull`
- [x] Dependencias PHP validadas (`composer install`)
- [x] Migracoes executadas
- [x] Cache limpo
- [x] Servicos reiniciados
- [x] Validacao HTTP concluida
- [x] Documentacao de deploy atualizada
