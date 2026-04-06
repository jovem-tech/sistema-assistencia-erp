# Atualizacao completa da VPS - Release v2.9.2

Data da execucao: 30/03/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Escopo publicado

- correcao de caracteres estranhos na busca global da navbar
- inclusao explicita do filtro `OS Legado` na busca global
- sincronizacao completa do frontend e backend do ERP
- sincronizacao completa dos uploads publicos
- substituicao integral da base `sistema_hml` da VPS pelo estado do banco local `assistencia_tecnica`
- preservacao, na VPS, do estado atual da migracao legada vinda do banco `erp`

## Procedimento executado

### 1. Geracao local dos artefatos

- dump SQL sem BOM e em `utf8mb4`:
  - `deploy/assistencia_tecnica_sync_20260330_043120.sql`
- pacote do codigo e uploads:
  - `deploy/sistema_hml_sync_20260330_043201.tar.gz`

### 2. Publicacao na VPS

Arquivos enviados para:

```text
/root/deploy_sync_20260330_043201/
```

### 3. Aplicacao remota

```bash
tar -xzf /root/deploy_sync_20260330_043201/sistema_hml_sync_20260330_043201.tar.gz -C /root/deploy_sync_20260330_043201/package
rm -rf /var/www/sistema-hml/vendor
rsync -a --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='backups/' \
  --exclude='/vendor/' \
  --exclude='node_modules/' \
  --exclude='writable/' \
  /root/deploy_sync_20260330_043201/package/ /var/www/sistema-hml/
cd /var/www/sistema-hml
composer install --no-dev --optimize-autoloader --no-interaction
mysql --binary-mode=1 --default-character-set=utf8mb4 -u sistema_hml -p'***' sistema_hml < /root/deploy_sync_20260330_043120.sql
php spark migrate --all --no-header
php spark cache:clear
systemctl restart php8.3-fpm
systemctl restart nginx
```

## Validacao pos-deploy

### Status de servicos

- `php8.3-fpm`: `active`
- `nginx`: `active`

### Health check HTTP

- `GET /` -> `200`
- `GET /login` -> `200`

### Validacao de assets estaticos

Endpoints conferidos com retorno `200`:

- `/assets/vendor/bootstrap/css/bootstrap.min.css`
- `/assets/vendor/bootstrap-icons/css/bootstrap-icons.css`
- `/assets/vendor/datatables/css/dataTables.bootstrap5.min.css`
- `/assets/vendor/select2/css/select2.min.css`
- `/assets/vendor/select2-bootstrap-5-theme/css/select2-bootstrap-5-theme.min.css`
- `/assets/vendor/sweetalert2/sweetalert2.min.css`
- `/assets/vendor/jquery/jquery-3.7.1.min.js`
- `/assets/vendor/bootstrap/js/bootstrap.bundle.min.js`
- `/assets/vendor/datatables/js/jquery.dataTables.min.js`
- `/assets/vendor/datatables/js/dataTables.bootstrap5.min.js`
- `/assets/vendor/chart.js/chart.umd.min.js`
- `/assets/vendor/jquery-mask-plugin/jquery.mask.min.js`
- `/assets/vendor/select2/js/select2.min.js`
- `/assets/vendor/sweetalert2/sweetalert2.all.min.js`

### Contagens de banco validadas na VPS

- `clientes`: `1294`
- `equipamentos`: `3560`
- `os`: `3560`
- `os_itens`: `2298`
- `os_status_historico`: `3927`
- `os_notas_legadas`: `14`
- `os_defeitos`: `18`

## Observacoes operacionais

- Nao foi realizado backup da base antiga da VPS por decisao operacional explicita, porque os dados existentes eram apenas de teste.
- O deploy substituiu a base pequena da VPS pelo mesmo estado migrado e validado localmente.
- A base remota agora contem as OS legadas importadas do `erp`, com rastreabilidade por `numero_os_legado` e `legacy_origem`.
- Houve uma correcao operacional pos-deploy para repor `public/assets/vendor`, porque a exclusao generica `vendor/` em `rsync` removeu indevidamente bibliotecas estaticas do frontend.

## Checklist de fechamento

- [x] frontend sincronizado
- [x] backend sincronizado
- [x] uploads sincronizados
- [x] assets estaticos validados
- [x] banco de dados sincronizado
- [x] dependencias PHP reinstaladas
- [x] migracoes reexecutadas
- [x] cache limpo
- [x] servicos reiniciados
- [x] validacao HTTP concluida
- [x] validacao de contagens concluida
