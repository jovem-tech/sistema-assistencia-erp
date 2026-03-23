# Manual Tecnico Oficial - Deploy ERP CodeIgniter 4 em VPS Ubuntu 24.04

Atualizado em 21/03/2026.

Este documento oficializa o procedimento completo de deploy para o ERP (CodeIgniter 4) com Nginx, PHP-FPM, MySQL e phpMyAdmin.

## 1. Visao geral da arquitetura

### 1.1 Fluxo da aplicacao

```text
Navegador -> Nginx -> PHP-FPM -> CodeIgniter 4 -> MySQL
```

### 1.2 Onde entra o phpMyAdmin

```text
Navegador -> Nginx (/phpmyadmin) -> PHP-FPM -> phpMyAdmin -> MySQL
```

### 1.3 Conceitos essenciais

- Nginx e o servidor web/reverse proxy.
- PHP-FPM executa codigo PHP.
- Nginx nao executa PHP sozinho; ele encaminha para o PHP-FPM.
- Socket Unix e o arquivo local usado para comunicacao Nginx <-> PHP-FPM (ex.: `/run/php/php-fpm.sock`).
- Virtual host e o arquivo de configuracao do site no Nginx.
- `.env` no CI4 define ambiente, banco, URL base e parametros de runtime.
- Linhas com `#` no `.env` sao comentarios e nao sao aplicadas.
- `user@localhost` no MySQL significa conta especifica para conexao local.
- `root` Linux e diferente de `root` MySQL.
- Em producao, use usuario dedicado de banco para a aplicacao.

### 1.4 Caminhos principais

- Aplicacao: `/var/www/sistema-hml`
- Entrada web do CI4: `/var/www/sistema-hml/public`
- VHost Nginx: `/etc/nginx/sites-available/sistema-hml`
- Link ativo Nginx: `/etc/nginx/sites-enabled/sistema-hml`
- Config PHP-FPM: `/etc/php/8.3/fpm/php.ini`
- Logs Nginx: `/var/log/nginx/error.log`
- Logs CI4: `/var/www/sistema-hml/writable/logs`

---

## 2. Fase 0 - Preparacao

### Objetivo

Preparar VPS limpa com pacotes base e estrutura inicial de deploy.

### Comandos

```bash
sudo apt update
sudo apt -y upgrade
sudo timedatectl set-timezone America/Sao_Paulo
sudo apt install -y curl wget git unzip zip ca-certificates software-properties-common gnupg lsb-release ufw htop jq
sudo mkdir -p /var/www/sistema-hml
sudo chown -R $USER:$USER /var/www/sistema-hml
```

### Resultado esperado

- Sistema atualizado.
- Timezone correto.
- Diretorio de deploy pronto.

### Validacao

```bash
date
ls -ld /var/www/sistema-hml
```

### Erros comuns

- Lock do `apt`: aguardar processo anterior terminar.
- DNS/repositorio inacessivel: validar rede e `/etc/resolv.conf`.

---

## 3. Fase 1 - Stack web

### Objetivo

Instalar Nginx + PHP 8.3 + PHP-FPM + extensoes necessarias para CI4.

### Comandos

```bash
sudo apt install -y nginx
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-opcache
sudo systemctl enable nginx
sudo systemctl start nginx
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
```

### Descobrir servico/sock reais (obrigatorio)

```bash
systemctl list-unit-files --type=service | grep -E "php.*fpm"
ls -lah /run/php/
```

### Resultado esperado

- `nginx` e `php8.3-fpm` em `active (running)`.
- Socket presente em `/run/php/`.

### Validacao

```bash
sudo systemctl --no-pager -l status nginx
sudo systemctl --no-pager -l status php8.3-fpm
```

### Erros comuns

- `php-fpm.service not found`: usar `php8.3-fpm.service`.
- Porta 80 ocupada por Apache: ver troubleshooting.

---

## 4. Fase 2 - Banco

### Objetivo

Instalar MySQL, criar banco da app e usuario dedicado.

### Comandos

```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql
```

### Criacao de banco e usuario (via root local)

```bash
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS sistema_hml
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

DROP USER IF EXISTS 'sistema_hml'@'localhost';
CREATE USER 'sistema_hml'@'localhost' IDENTIFIED BY 'TroquePorSenhaForte@2026';
GRANT ALL PRIVILEGES ON sistema_hml.* TO 'sistema_hml'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### Validacao

```bash
mysql -u sistema_hml -p -h 127.0.0.1 -e "SHOW DATABASES LIKE 'sistema_hml';"
```

### Erros comuns

- `ERROR 1396`: usuario alvo nao existe para `ALTER USER`.
- `ERROR 1410`: conta atual sem permissao para `GRANT`.
- Correcao: entrar com `sudo mysql`, usar `DROP USER IF EXISTS` + `CREATE USER` + `GRANT`.

---

## 5. Fase 3 - Aplicacao CodeIgniter

### Objetivo

Publicar codigo, configurar `.env`, permissoes, vhost Nginx e CI4.

### Codigo da aplicacao

```bash
cd /var/www
sudo rm -rf /var/www/sistema-hml
sudo git clone <URL_DO_REPOSITORIO> /var/www/sistema-hml
cd /var/www/sistema-hml
composer install --no-dev --optimize-autoloader
```

### `.env` (linhas ativas, sem `#`)

```ini
CI_ENVIRONMENT = production

app.baseURL = 'http://161.97.93.120/'
app.forceGlobalSecureRequests = false
app.CSPEnabled = false

database.default.hostname = 127.0.0.1
database.default.database = sistema_hml
database.default.username = sistema_hml
database.default.password = 'TroquePorSenhaForte@2026'
database.default.DBDriver = MySQLi
database.default.port = 3306

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.savePath = '/var/www/sistema-hml/writable/session'
logger.threshold = 4
```

### Permissoes

```bash
sudo chown -R www-data:www-data /var/www/sistema-hml
sudo find /var/www/sistema-hml/writable -type d -exec chmod 775 {} \;
sudo find /var/www/sistema-hml/writable -type f -exec chmod 664 {} \;
```

### VHost Nginx (ERP)

```bash
PHP_SOCK=$(ls /run/php/php*-fpm.sock /run/php/php-fpm.sock 2>/dev/null | head -n1)
echo "Socket: $PHP_SOCK"
```

```nginx
server {
    listen 80;
    server_name 161.97.93.120;
    client_max_body_size 512M;

    root /var/www/sistema-hml/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> Use o socket detectado no `fastcgi_pass`.

### Ativar vhost

```bash
sudo rm -f /etc/nginx/sites-enabled/default
sudo ln -sfn /etc/nginx/sites-available/sistema-hml /etc/nginx/sites-enabled/sistema-hml
sudo nginx -t
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

### CI4 runtime

```bash
cd /var/www/sistema-hml
php spark key:generate
php spark cache:clear
php spark migrate --all
```

---

## 6. Fase 4 - phpMyAdmin

### Objetivo

Publicar phpMyAdmin no Nginx sem conflito com roteamento do CI4.

### Instalacao

```bash
sudo apt install -y phpmyadmin php-mbstring php-zip php-gd php-curl php8.3-mysql
```

### Blocos Nginx recomendados (antes da regra PHP generica)

```nginx
location = /phpmyadmin {
    return 301 /phpmyadmin/;
}

location /phpmyadmin/ {
    root /usr/share;
    index index.php index.html index.htm;
    try_files $uri $uri/ /phpmyadmin/index.php?$query_string;
}

location ~ ^/phpmyadmin/(.+\.php)$ {
    root /usr/share;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /usr/share/phpmyadmin/$1;
    fastcgi_pass unix:/run/php/php-fpm.sock;
}
```

### Validacao

```bash
sudo nginx -t
sudo systemctl reload nginx
curl -I http://161.97.93.120/phpmyadmin/
curl -I http://161.97.93.120/phpmyadmin/index.php
```

---

## 7. Fase 5 - Importacao da base

### Metodo recomendado (dump grande): terminal

```bash
mysql -u sistema_hml -p sistema_hml < /root/seu_dump.sql
```

### Metodo via phpMyAdmin

1. Acessar `/phpmyadmin`.
2. Selecionar banco `sistema_hml`.
3. Importar arquivo SQL.

### Se ocorrer 413 Request Entity Too Large

```bash
# Nginx
sudo sed -ri 's~client_max_body_size\s+[^;]+;~client_max_body_size 512M;~g' /etc/nginx/sites-available/sistema-hml
grep -q "client_max_body_size" /etc/nginx/sites-available/sistema-hml || \
sudo sed -i '/server_name 161.97.93.120;/a\    client_max_body_size 512M;' /etc/nginx/sites-available/sistema-hml

# PHP-FPM
PHPINI=/etc/php/8.3/fpm/php.ini
sudo sed -ri 's~^;?\s*upload_max_filesize\s*=.*~upload_max_filesize = 512M~' $PHPINI
sudo sed -ri 's~^;?\s*post_max_size\s*=.*~post_max_size = 512M~' $PHPINI
sudo sed -ri 's~^;?\s*max_execution_time\s*=.*~max_execution_time = 600~' $PHPINI
sudo sed -ri 's~^;?\s*max_input_time\s*=.*~max_input_time = 600~' $PHPINI
sudo sed -ri 's~^;?\s*memory_limit\s*=.*~memory_limit = 1024M~' $PHPINI

sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm
```

---

## 8. Fase 6 - Validacao ponta a ponta

### Comandos

```bash
sudo systemctl --no-pager -l status nginx
sudo systemctl --no-pager -l status php8.3-fpm
sudo systemctl --no-pager -l status mysql

curl -I http://161.97.93.120/
curl -I http://161.97.93.120/login
curl -I http://161.97.93.120/phpmyadmin/

mysql -u sistema_hml -p -h 127.0.0.1 -e "SHOW DATABASES LIKE 'sistema_hml';"

cd /var/www/sistema-hml
php spark routes | head -n 60
```

### Critrio de aceite

- Servicos ativos (`nginx`, `php8.3-fpm`, `mysql`).
- Aplicacao responde em `/login`.
- phpMyAdmin responde em `/phpmyadmin/`.
- Banco acessivel com usuario dedicado.

---

## 9. Troubleshooting detalhado (casos reais)

### 9.1 `.env` comentado

- Sintoma: app nao aplica DB/baseURL.
- Causa raiz: linhas com `#`.
- Diagnostico: `grep -n "database.default" /var/www/sistema-hml/.env`
- Correcao: remover `#` e salvar.
- Prevencao: checklist de `.env` apos deploy.

### 9.2 Placeholders literais no vhost

- Sintoma: vhost incoerente.
- Causa raiz: `${SERVER_IP}` / `${APP_DIR}` gravados literal.
- Diagnostico: `grep -nE 'SERVER_IP|APP_DIR' /etc/nginx/sites-available/sistema-hml`
- Correcao: substituir por valores reais.
- Prevencao: revisar arquivo antes de `nginx -t`.

### 9.3 Conflito porta 80 (Apache)

- Sintoma: `bind() to 0.0.0.0:80 failed`.
- Diagnostico:

```bash
ss -ltnp | grep ':80'
systemctl status apache2
```

- Correcao:

```bash
sudo systemctl stop apache2
sudo systemctl disable apache2
sudo systemctl mask apache2
sudo systemctl restart nginx
```

### 9.4 Servico PHP-FPM incorreto

- Sintoma: `php-fpm.service not found`.
- Correcao: usar `php8.3-fpm.service`.
- Diagnostico:

```bash
systemctl list-unit-files --type=service | grep -E "php.*fpm"
```

### 9.5 Base divergente da local

- Causas:
  - dump incorreto
  - `.env` divergente
  - cache
  - baseURL errada
- Checklist:

```bash
php spark routes | grep -E "atendimento-whatsapp|crm/metricas-marketing|login"
grep -E "app.baseURL|database.default" .env
php spark cache:clear
```

### 9.6 `/phpmyadmin` 404

- Causa: `location` ausente ou conflito com CI4.
- Correcao: publicar blocos dedicados de phpMyAdmin.

### 9.7 Conflito location/fastcgi phpMyAdmin

- Causa comum: bloco generico PHP capturando rota.
- Correcao: regex phpMyAdmin especifica, antes da regra PHP generica.

### 9.8 MySQL `ERROR 1396` / `ERROR 1410`

- `1396`: alteracao de usuario inexistente.
- `1410`: conta sem privilegios para grant/create.
- Correcao: `sudo mysql` + `DROP USER IF EXISTS` + `CREATE USER` + `GRANT`.

### 9.9 Erro 413

- Causa: limite baixo de upload/post.
- Correcao: `client_max_body_size` + `upload_max_filesize` + `post_max_size`.

---

## 10. Checklist final operacional

- [ ] `.env` ativo e sem comentarios nas chaves criticas
- [ ] VHost aponta para `/public`
- [ ] Socket PHP correto no `fastcgi_pass`
- [ ] `nginx -t` sem erro
- [ ] `nginx`, `php8.3-fpm`, `mysql` ativos
- [ ] Banco e usuario dedicados validados
- [ ] Migrations e cache executados
- [ ] `/login` e `/phpmyadmin/` funcionando
- [ ] Limites de upload ajustados (se dump grande)
- [ ] Logs sem erro critico

---

## 11. Hardening basico pos-deploy

### HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com -d www.seu-dominio.com
```

### Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status verbose
```

### Recomendacoes

- Restringir phpMyAdmin por IP/BasicAuth.
- Nao usar root para operacao diaria.
- Criar rotinas de backup e restore testado.

---

## 12. Runbook de manutencao

### Cofre local de credenciais (operacao)

Para execucao operacional assistida por IA/local, manter credenciais tecnicas apenas em arquivo local nao versionado:

- `.codex_keys/vps_credentials.local.md`

Regras:
- nunca commitar esse arquivo;
- revisar credenciais apos qualquer troca de senha;
- migrar gradualmente para autenticacao por chave SSH.

### Ver logs

```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
tail -f /var/www/sistema-hml/writable/logs/log-$(date +%Y-%m-%d).log
```

### Reiniciar servicos

```bash
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo systemctl restart mysql
```

### Backup e restore banco

```bash
mysqldump -u sistema_hml -p sistema_hml > /root/backup_sistema_hml_$(date +%F_%H%M).sql
mysql -u sistema_hml -p sistema_hml < /root/backup_sistema_hml_YYYY-MM-DD_HHMM.sql
```

### Atualizar aplicacao e rollback

```bash
cd /var/www/sistema-hml
git fetch --all
git checkout <branch-ou-tag>
git pull
composer install --no-dev --optimize-autoloader
php spark migrate --all
php spark cache:clear
sudo systemctl restart php8.3-fpm nginx
```

```bash
cd /var/www/sistema-hml
git reflog
git checkout <commit-anterior>
composer install --no-dev --optimize-autoloader
php spark cache:clear
sudo systemctl restart php8.3-fpm nginx
```

---

## 13. Controle de versao e release

### Fonte da versao exibida no ERP

- Rodape lateral do sistema: `Versao x.y.z`.
- Fonte padrao: `app/Config/SystemRelease.php` (`public string $version`).
- Override opcional por banco: chave `sistema_versao` na tabela `configuracoes`.
- Regra do helper: `get_system_version()` usa banco primeiro e fallback no arquivo de release.

Override opcional via SQL:

```sql
INSERT INTO configuracoes (chave, valor, created_at, updated_at)
VALUES ('sistema_versao', '2.1.0', NOW(), NOW())
ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW();
```

### Fluxo recomendado de release

```bash
# local (desenvolvimento)
git checkout main
git pull origin main
git add .
git commit -m "feat(release): dashboard responsivo + versao no rodape"
git tag -a v2.1.0 -m "Release v2.1.0"
git push origin main --tags
```

### Update seguro na VPS (sem reinstalar)

```bash
cd /var/www/sistema-hml
git fetch --all --tags
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader
php spark migrate --all
php spark cache:clear
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

### Validacao de release em producao

```bash
curl -I http://SEU_IP_OU_DOMINIO/login
php spark --version
git describe --tags --always
```

Checklist visual:
- rodape lateral exibindo a versao esperada;
- dashboard abrindo sem erro JS/PHP;
- modais de `Ultimas OS` funcionando.

---

## 14. Anexo com comandos de diagnostico rapido

```bash
# porta 80
ss -ltnp | grep ':80'
lsof -i :80

# nginx
nginx -t
systemctl status nginx --no-pager -l
nginx -T | grep -n "server_name\|phpmyadmin\|client_max_body_size"

# php-fpm
systemctl list-unit-files --type=service | grep -E "php.*fpm"
ls -lah /run/php/
systemctl status php8.3-fpm --no-pager -l

# mysql
systemctl status mysql --no-pager -l
mysql -u sistema_hml -p -h 127.0.0.1 -e "SHOW DATABASES;"

# .env
grep -nE "CI_ENVIRONMENT|app.baseURL|database.default|session.savePath|logger.threshold" /var/www/sistema-hml/.env

# upload limits
nginx -T | grep -n client_max_body_size
grep -E "upload_max_filesize|post_max_size|max_execution_time|max_input_time|memory_limit" /etc/php/8.3/fpm/php.ini
```

---

## 15. Script oficial de automacao (integral)

O script completo esta versionado em:

- `documentacao/10-deploy/scripts/install_erp.sh`

Execucao:

```bash
chmod +x documentacao/10-deploy/scripts/install_erp.sh
sudo ./documentacao/10-deploy/scripts/install_erp.sh
```
