#!/usr/bin/env bash
# install_erp.sh
# Provisionamento e deploy automatizado de ERP CodeIgniter 4
# Ubuntu 24.04 + Nginx + PHP-FPM + MySQL + phpMyAdmin
#
# Uso:
#   chmod +x install_erp.sh
#   sudo ./install_erp.sh
#
# Observacoes:
# - Script idempotente: pode ser executado novamente sem quebrar o ambiente.
# - Solicita dados sensiveis de forma interativa (sem exibir senha).
# - Nao imprime senha em logs.

set -Eeuo pipefail
IFS=$'\n\t'

# ==============================
# Cores e logs
# ==============================
C_RESET='\033[0m'
C_RED='\033[0;31m'
C_GREEN='\033[0;32m'
C_YELLOW='\033[1;33m'
C_BLUE='\033[0;34m'

log_info()    { echo -e "${C_BLUE}[INFO]${C_RESET} $*"; }
log_warn()    { echo -e "${C_YELLOW}[WARN]${C_RESET} $*"; }
log_error()   { echo -e "${C_RED}[ERRO]${C_RESET} $*" >&2; }
log_success() { echo -e "${C_GREEN}[OK]${C_RESET} $*"; }

on_error() {
  local line="$1"
  local cmd="$2"
  log_error "Falha na linha ${line}: ${cmd}"
  log_error "Abortando para evitar estado inconsistente."
  exit 1
}
trap 'on_error "$LINENO" "$BASH_COMMAND"' ERR

# ==============================
# Variaveis globais
# ==============================
APP_DIR=""
APP_DB=""
APP_DB_USER=""
APP_DB_PASS=""
GIT_REPO_URL=""
GIT_BRANCH="main"
SERVER_NAME=""
HTTP_PORT="80"
INSTALL_PHPMYADMIN="n"
IMPORT_DUMP="n"
DUMP_PATH=""
RUN_MIGRATIONS="s"
USE_GIT_TOKEN="n"
GIT_AUTH_USER="oauth2"
GIT_TOKEN=""

SITE_SLUG=""
VHOST_FILE=""
PHP_VERSION=""
PHP_FPM_SERVICE=""
PHP_FPM_SOCK=""
PHP_FPM_INI=""
APP_URL=""

# ==============================
# Utilitarios
# ==============================
require_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    log_info "Elevando privilegios com sudo..."
    exec sudo -E bash "$0" "$@"
  fi
}

backup_file() {
  local f="$1"
  if [[ -f "$f" ]]; then
    local ts
    ts="$(date +%Y%m%d_%H%M%S)"
    cp -a "$f" "${f}.bak.${ts}"
    log_info "Backup criado: ${f}.bak.${ts}"
  fi
}

validate_identifier() {
  local val="$1"
  [[ "$val" =~ ^[A-Za-z0-9_]+$ ]]
}

prompt_non_empty() {
  local var_name="$1"
  local prompt="$2"
  local default="${3:-}"
  local value=""
  while true; do
    if [[ -n "$default" ]]; then
      read -r -p "${prompt} [${default}]: " value
      value="${value:-$default}"
    else
      read -r -p "${prompt}: " value
    fi
    if [[ -n "$value" ]]; then
      printf -v "$var_name" "%s" "$value"
      return 0
    fi
    log_warn "Valor obrigatorio."
  done
}

prompt_yes_no() {
  local var_name="$1"
  local prompt="$2"
  local default="${3:-n}"
  local value=""
  while true; do
    read -r -p "${prompt} [s/n] (padrao: ${default}): " value
    value="${value:-$default}"
    value="$(echo "$value" | tr '[:upper:]' '[:lower:]')"
    if [[ "$value" == "s" || "$value" == "n" ]]; then
      printf -v "$var_name" "%s" "$value"
      return 0
    fi
    log_warn "Resposta invalida. Use s ou n."
  done
}

prompt_password_confirm() {
  local var_name="$1"
  local prompt="$2"
  local p1=""
  local p2=""
  while true; do
    read -r -s -p "${prompt}: " p1
    echo
    read -r -s -p "Confirme a senha: " p2
    echo
    if [[ -z "$p1" ]]; then
      log_warn "Senha nao pode ser vazia."
      continue
    fi
    if [[ "$p1" != "$p2" ]]; then
      log_warn "As senhas nao conferem. Tente novamente."
      continue
    fi
    printf -v "$var_name" "%s" "$p1"
    return 0
  done
}

urlencode() {
  python3 -c 'import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1], safe=""))' "$1"
}

escape_sql_string() {
  printf "%s" "$1" | sed "s/'/''/g"
}

upsert_env() {
  local file="$1"
  local key="$2"
  local value="$3"

  local escaped_key
  escaped_key="$(printf '%s' "$key" | sed 's/[]\/$*.^[]/\\&/g')"

  if grep -Eq "^[#[:space:]]*${escaped_key}[[:space:]]*=" "$file"; then
    sed -ri "s|^[#[:space:]]*${escaped_key}[[:space:]]*=.*|${key} = ${value}|g" "$file"
  else
    printf "\n%s = %s\n" "$key" "$value" >> "$file"
  fi
}

mysql_root_exec() {
  mysql --protocol=socket -uroot "$@"
}

create_mysql_client_cnf() {
  local file="$1"
  cat > "$file" <<EOF
[client]
host=127.0.0.1
user=${APP_DB_USER}
password=${APP_DB_PASS}
EOF
  chmod 600 "$file"
}

# ==============================
# Entrada de dados
# ==============================
collect_inputs() {
  log_info "Coletando parametros do deploy..."

  prompt_non_empty APP_DIR "Diretorio absoluto da aplicacao (APP_DIR)" "/var/www/sistema-hml"
  prompt_non_empty APP_DB "Nome do banco (APP_DB)" "sistema_hml"
  prompt_non_empty APP_DB_USER "Usuario do banco (APP_DB_USER)" "sistema_hml"
  prompt_password_confirm APP_DB_PASS "Senha do banco (APP_DB_PASS)"

  prompt_non_empty GIT_REPO_URL "URL do repositorio Git (HTTPS ou SSH)"
  prompt_non_empty GIT_BRANCH "Branch a publicar" "main"
  prompt_non_empty SERVER_NAME "Dominio ou IP do servidor (server_name Nginx)" "161.97.93.120"
  prompt_non_empty HTTP_PORT "Porta HTTP do Nginx" "80"

  prompt_yes_no INSTALL_PHPMYADMIN "Instalar phpMyAdmin?" "s"
  prompt_yes_no RUN_MIGRATIONS "Executar migrations do CodeIgniter?" "s"

  prompt_yes_no USE_GIT_TOKEN "Repositorio privado via token HTTPS?" "n"
  if [[ "$USE_GIT_TOKEN" == "s" ]]; then
    prompt_non_empty GIT_AUTH_USER "Usuario para autenticacao HTTPS (ex.: oauth2 ou x-token-auth)" "oauth2"
    prompt_password_confirm GIT_TOKEN "Token Git (nao sera exibido)"
  fi

  prompt_yes_no IMPORT_DUMP "Importar dump SQL automaticamente?" "n"
  if [[ "$IMPORT_DUMP" == "s" ]]; then
    prompt_non_empty DUMP_PATH "Caminho absoluto do dump SQL (.sql ou .sql.gz)"
  fi

  if [[ ! "$APP_DIR" =~ ^/ ]]; then
    log_error "APP_DIR deve ser caminho absoluto. Exemplo: /var/www/sistema-hml"
    exit 1
  fi

  if ! validate_identifier "$APP_DB"; then
    log_error "APP_DB invalido. Use apenas letras, numeros e underscore."
    exit 1
  fi
  if ! validate_identifier "$APP_DB_USER"; then
    log_error "APP_DB_USER invalido. Use apenas letras, numeros e underscore."
    exit 1
  fi

  if [[ "$IMPORT_DUMP" == "s" && ! -f "$DUMP_PATH" ]]; then
    log_error "Dump informado nao existe: $DUMP_PATH"
    exit 1
  fi

  SITE_SLUG="$(basename "$APP_DIR" | tr -cd '[:alnum:]_-')"
  SITE_SLUG="${SITE_SLUG:-erp-ci4}"
  VHOST_FILE="/etc/nginx/sites-available/${SITE_SLUG}"

  APP_URL="http://${SERVER_NAME}"
  if [[ "$HTTP_PORT" != "80" ]]; then
    APP_URL="${APP_URL}:${HTTP_PORT}"
  fi
  APP_URL="${APP_URL}/"

  log_success "Parametros coletados com sucesso."
}

# ==============================
# Preparacao do sistema
# ==============================
prepare_system() {
  log_info "Atualizando sistema e instalando pacotes base..."
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get -y upgrade
  apt-get install -y curl wget git unzip zip ca-certificates gnupg lsb-release software-properties-common ufw jq htop
  log_success "Sistema preparado."
}

handle_port_conflicts() {
  log_info "Verificando conflitos de porta ${HTTP_PORT}..."
  local listeners
  listeners="$(ss -ltnp 2>/dev/null | grep -E ":${HTTP_PORT}[[:space:]]" || true)"

  if [[ -z "$listeners" ]]; then
    log_success "Nenhum conflito encontrado na porta ${HTTP_PORT}."
    return
  fi

  if [[ "$HTTP_PORT" == "80" ]] && systemctl is-active --quiet apache2; then
    log_warn "Apache detectado na porta 80. Parando e desabilitando Apache..."
    systemctl stop apache2 || true
    systemctl disable apache2 || true
    systemctl mask apache2 || true
    log_success "Apache removido do caminho da porta 80."
    return
  fi

  if echo "$listeners" | grep -q nginx; then
    log_info "Nginx ja esta usando a porta ${HTTP_PORT}. Seguiremos."
    return
  fi

  log_warn "Ha outro processo usando a porta ${HTTP_PORT}:"
  echo "$listeners"
  log_error "Conflito nao tratado automaticamente. Libere a porta e rode novamente."
  exit 1
}

# ==============================
# Stack web (Nginx + PHP)
# ==============================
install_web_stack() {
  log_info "Instalando Nginx, PHP 8.3 e extensoes..."
  export DEBIAN_FRONTEND=noninteractive
  apt-get install -y nginx
  apt-get install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-opcache

  systemctl enable nginx
  systemctl start nginx

  detect_php_runtime
  systemctl enable "$PHP_FPM_SERVICE"
  systemctl start "$PHP_FPM_SERVICE"

  configure_php_limits
  log_success "Stack web instalada e configurada."
}

detect_php_runtime() {
  log_info "Detectando versao PHP, servico FPM e socket..."
  PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
  local preferred_service="php${PHP_VERSION}-fpm"

  if systemctl list-unit-files --type=service | awk '{print $1}' | grep -qx "${preferred_service}.service"; then
    PHP_FPM_SERVICE="$preferred_service"
  else
    PHP_FPM_SERVICE="$(systemctl list-unit-files --type=service | awk '/php[0-9]+\.[0-9]+-fpm\.service/{gsub(/\.service$/,"",$1); print $1; exit}')"
  fi

  if [[ -z "${PHP_FPM_SERVICE:-}" ]]; then
    log_error "Nao foi possivel detectar servico PHP-FPM."
    exit 1
  fi

  if [[ -S /run/php/php-fpm.sock ]]; then
    PHP_FPM_SOCK="/run/php/php-fpm.sock"
  else
    PHP_FPM_SOCK="$(find /run/php -maxdepth 1 -type s -name "php*-fpm.sock" | sort | head -n1 || true)"
  fi

  if [[ -z "${PHP_FPM_SOCK:-}" ]]; then
    log_error "Nao foi possivel detectar socket PHP-FPM em /run/php."
    exit 1
  fi

  local fpm_mm="${PHP_FPM_SERVICE#php}"
  fpm_mm="${fpm_mm%-fpm}"
  PHP_FPM_INI="/etc/php/${fpm_mm}/fpm/php.ini"
  if [[ ! -f "$PHP_FPM_INI" ]]; then
    PHP_FPM_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
  fi

  log_info "PHP_VERSION=${PHP_VERSION}"
  log_info "PHP_FPM_SERVICE=${PHP_FPM_SERVICE}"
  log_info "PHP_FPM_SOCK=${PHP_FPM_SOCK}"
  log_info "PHP_FPM_INI=${PHP_FPM_INI}"
}

configure_php_limits() {
  log_info "Ajustando limites PHP para uploads e importacao..."
  if [[ ! -f "$PHP_FPM_INI" ]]; then
    log_warn "php.ini do FPM nao encontrado. Pulando ajuste."
    return
  fi

  sed -ri 's~^;?\s*upload_max_filesize\s*=.*~upload_max_filesize = 512M~' "$PHP_FPM_INI"
  sed -ri 's~^;?\s*post_max_size\s*=.*~post_max_size = 512M~' "$PHP_FPM_INI"
  sed -ri 's~^;?\s*max_execution_time\s*=.*~max_execution_time = 600~' "$PHP_FPM_INI"
  sed -ri 's~^;?\s*max_input_time\s*=.*~max_input_time = 600~' "$PHP_FPM_INI"
  sed -ri 's~^;?\s*memory_limit\s*=.*~memory_limit = 1024M~' "$PHP_FPM_INI"

  systemctl restart "$PHP_FPM_SERVICE"
  log_success "Limites PHP ajustados."
}

# ==============================
# MySQL
# ==============================
install_mysql() {
  log_info "Instalando e iniciando MySQL..."
  export DEBIAN_FRONTEND=noninteractive
  apt-get install -y mysql-server
  systemctl enable mysql
  systemctl start mysql

  if ! mysqladmin ping --silent; then
    log_error "MySQL nao respondeu ao ping."
    exit 1
  fi
  if ! mysql_root_exec -e "SELECT 1;" >/dev/null 2>&1; then
    log_error "Nao foi possivel acessar MySQL como root local (socket)."
    exit 1
  fi
  log_success "MySQL instalado e acessivel."
}

configure_mysql_database() {
  log_info "Criando banco e usuario da aplicacao..."
  local db_pass_sql
  db_pass_sql="$(escape_sql_string "$APP_DB_PASS")"

  mysql_root_exec <<SQL
CREATE DATABASE IF NOT EXISTS \`${APP_DB}\`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${APP_DB_USER}'@'localhost' IDENTIFIED BY '${db_pass_sql}';
ALTER USER '${APP_DB_USER}'@'localhost' IDENTIFIED BY '${db_pass_sql}';
GRANT ALL PRIVILEGES ON \`${APP_DB}\`.* TO '${APP_DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

  local tmpcnf
  tmpcnf="$(mktemp)"
  create_mysql_client_cnf "$tmpcnf"
  mysql --defaults-extra-file="$tmpcnf" -e "SHOW DATABASES LIKE '${APP_DB}';" | grep -q "${APP_DB}" || {
    rm -f "$tmpcnf"
    log_error "Falha ao validar conexao com usuario da aplicacao."
    exit 1
  }
  rm -f "$tmpcnf"
  log_success "Banco e usuario configurados."
}

# ==============================
# Composer
# ==============================
install_composer_if_needed() {
  if command -v composer >/dev/null 2>&1; then
    log_info "Composer ja instalado: $(composer --version)"
    return
  fi

  log_info "Instalando Composer..."
  local expected actual
  expected="$(wget -q -O - https://composer.github.io/installer.sig)"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  actual="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
  if [[ "$expected" != "$actual" ]]; then
    rm -f composer-setup.php
    log_error "Assinatura do Composer invalida."
    exit 1
  fi
  php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f composer-setup.php
  log_success "Composer instalado."
}

# ==============================
# Aplicacao CodeIgniter
# ==============================
build_repo_url() {
  if [[ "$USE_GIT_TOKEN" == "s" ]]; then
    if [[ "$GIT_REPO_URL" =~ ^https:// ]]; then
      local enc_token
      enc_token="$(urlencode "$GIT_TOKEN")"
      echo "${GIT_REPO_URL/https:\/\//https://${GIT_AUTH_USER}:${enc_token}@}"
      return
    else
      log_warn "Token informado, mas URL nao eh HTTPS. Token sera ignorado."
    fi
  fi
  echo "$GIT_REPO_URL"
}

deploy_application() {
  log_info "Publicando codigo da aplicacao..."
  local repo_url_auth
  repo_url_auth="$(build_repo_url)"

  mkdir -p "$APP_DIR"

  if [[ -d "$APP_DIR/.git" ]]; then
    log_info "Repositorio existente. Atualizando..."
    git -C "$APP_DIR" fetch --all --prune
    git -C "$APP_DIR" checkout "$GIT_BRANCH"
    git -C "$APP_DIR" pull --ff-only origin "$GIT_BRANCH"
  else
    if [[ -n "$(ls -A "$APP_DIR" 2>/dev/null)" ]]; then
      log_error "Diretorio $APP_DIR nao esta vazio e nao e repositorio Git."
      exit 1
    fi
    if ! git clone --branch "$GIT_BRANCH" --depth 1 "$repo_url_auth" "$APP_DIR"; then
      log_error "Falha no git clone. Verifique URL, branch e credenciais."
      exit 1
    fi
  fi

  if [[ ! -f "$APP_DIR/public/index.php" ]]; then
    log_error "Estrutura invalida: nao encontrei $APP_DIR/public/index.php"
    exit 1
  fi

  install_composer_if_needed
  export COMPOSER_ALLOW_SUPERUSER=1
  if ! (cd "$APP_DIR" && composer install --no-interaction --prefer-dist --optimize-autoloader); then
    log_error "Falha no composer install."
    exit 1
  fi

  configure_env_file
  set_app_permissions
  run_ci_tasks
  log_success "Aplicacao publicada."
}

configure_env_file() {
  log_info "Configurando .env..."
  local env_file="$APP_DIR/.env"
  if [[ ! -f "$env_file" ]]; then
    if [[ -f "$APP_DIR/env" ]]; then
      cp "$APP_DIR/env" "$env_file"
    else
      touch "$env_file"
    fi
  fi
  backup_file "$env_file"

  upsert_env "$env_file" "CI_ENVIRONMENT" "production"
  upsert_env "$env_file" "app.baseURL" "'${APP_URL}'"
  upsert_env "$env_file" "app.forceGlobalSecureRequests" "false"
  upsert_env "$env_file" "app.CSPEnabled" "false"

  upsert_env "$env_file" "database.default.hostname" "127.0.0.1"
  upsert_env "$env_file" "database.default.database" "${APP_DB}"
  upsert_env "$env_file" "database.default.username" "${APP_DB_USER}"
  upsert_env "$env_file" "database.default.password" "'${APP_DB_PASS}'"
  upsert_env "$env_file" "database.default.DBDriver" "MySQLi"
  upsert_env "$env_file" "database.default.port" "3306"

  upsert_env "$env_file" "session.driver" "'CodeIgniter\\Session\\Handlers\\FileHandler'"
  upsert_env "$env_file" "session.savePath" "'${APP_DIR}/writable/session'"
  upsert_env "$env_file" "logger.threshold" "4"

  log_success ".env atualizado."
}

set_app_permissions() {
  log_info "Ajustando permissoes..."
  mkdir -p "$APP_DIR/writable/session" "$APP_DIR/writable/uploads"
  chown -R www-data:www-data "$APP_DIR"
  if [[ -d "$APP_DIR/writable" ]]; then
    find "$APP_DIR/writable" -type d -exec chmod 775 {} \;
    find "$APP_DIR/writable" -type f -exec chmod 664 {} \;
  fi
  log_success "Permissoes ajustadas."
}

run_ci_tasks() {
  log_info "Executando tarefas do CodeIgniter..."
  cd "$APP_DIR"
  if [[ -f "$APP_DIR/spark" ]]; then
    php spark key:generate --force || log_warn "Nao foi possivel gerar key automaticamente."
    php spark cache:clear || log_warn "Falha ao limpar cache."
    if [[ "$RUN_MIGRATIONS" == "s" ]]; then
      php spark migrate --all --no-interaction || log_warn "Falha nas migrations."
    fi
  else
    log_warn "Arquivo spark nao encontrado. Pulando tarefas CI4."
  fi
}

# ==============================
# phpMyAdmin (opcional)
# ==============================
install_phpmyadmin_if_requested() {
  if [[ "$INSTALL_PHPMYADMIN" != "s" ]]; then
    log_info "phpMyAdmin nao sera instalado."
    return
  fi

  log_info "Instalando phpMyAdmin..."
  export DEBIAN_FRONTEND=noninteractive
  echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect none" | debconf-set-selections || true
  echo "phpmyadmin phpmyadmin/dbconfig-install boolean false" | debconf-set-selections || true
  apt-get install -y phpmyadmin php8.3-mysql php-mbstring php-zip php-gd php-curl

  if [[ ! -d /usr/share/phpmyadmin ]]; then
    log_error "phpMyAdmin nao encontrado em /usr/share/phpmyadmin"
    exit 1
  fi
  log_success "phpMyAdmin instalado."
}

# ==============================
# Nginx
# ==============================
build_nginx_vhost() {
  log_info "Gerando vhost Nginx..."
  backup_file "$VHOST_FILE"

  local pma_block=""
  if [[ "$INSTALL_PHPMYADMIN" == "s" ]]; then
    pma_block=$(cat <<EOF

    # phpMyAdmin
    location = /phpmyadmin {
        return 301 /phpmyadmin/;
    }

    location /phpmyadmin/ {
        root /usr/share;
        index index.php index.html index.htm;
        try_files \$uri \$uri/ /phpmyadmin/index.php?\$query_string;
    }

    location ~ ^/phpmyadmin/(.+\\.php)\$ {
        root /usr/share;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/phpmyadmin/\$1;
        fastcgi_pass unix:${PHP_FPM_SOCK};
    }
EOF
)
  fi

  cat > "$VHOST_FILE" <<EOF
server {
    listen ${HTTP_PORT};
    server_name ${SERVER_NAME};

    client_max_body_size 512M;

    root ${APP_DIR}/public;
    index index.php index.html index.htm;

${pma_block}

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\. {
        deny all;
    }
}
EOF

  ln -sfn "$VHOST_FILE" "/etc/nginx/sites-enabled/${SITE_SLUG}"
  rm -f /etc/nginx/sites-enabled/default || true

  nginx -t
  systemctl restart "$PHP_FPM_SERVICE"
  if ! systemctl restart nginx; then
    log_error "Falha ao reiniciar Nginx."
    journalctl -xeu nginx.service --no-pager | tail -n 80 || true
    exit 1
  fi

  log_success "Nginx configurado."
}

# ==============================
# Importacao dump (opcional)
# ==============================
import_dump_if_requested() {
  if [[ "$IMPORT_DUMP" != "s" ]]; then
    log_info "Importacao de dump nao solicitada."
    return
  fi

  log_info "Importando dump..."
  if [[ ! -f "$DUMP_PATH" ]]; then
    log_error "Dump nao encontrado: $DUMP_PATH"
    exit 1
  fi

  local tmpcnf
  tmpcnf="$(mktemp)"
  create_mysql_client_cnf "$tmpcnf"

  if [[ "$DUMP_PATH" =~ \.gz$ ]]; then
    gunzip -c "$DUMP_PATH" | mysql --defaults-extra-file="$tmpcnf" "$APP_DB"
  else
    mysql --defaults-extra-file="$tmpcnf" "$APP_DB" < "$DUMP_PATH"
  fi
  rm -f "$tmpcnf"
  log_success "Dump importado."
}

# ==============================
# Validacao final
# ==============================
final_validation() {
  log_info "Validando servicos e endpoints..."

  systemctl --no-pager -l status nginx >/dev/null
  systemctl --no-pager -l status "$PHP_FPM_SERVICE" >/dev/null
  systemctl --no-pager -l status mysql >/dev/null

  local app_code pma_code
  app_code="$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:${HTTP_PORT}/" || echo "000")"
  if [[ "$INSTALL_PHPMYADMIN" == "s" ]]; then
    pma_code="$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:${HTTP_PORT}/phpmyadmin/" || echo "000")"
  else
    pma_code="N/A"
  fi

  local tmpcnf
  tmpcnf="$(mktemp)"
  create_mysql_client_cnf "$tmpcnf"
  mysql --defaults-extra-file="$tmpcnf" -e "SHOW DATABASES LIKE '${APP_DB}';" | grep -q "$APP_DB"
  rm -f "$tmpcnf"

  echo
  echo "============================================================"
  echo "DEPLOY FINALIZADO"
  echo "============================================================"
  echo "URL do sistema: ${APP_URL}"
  if [[ "$INSTALL_PHPMYADMIN" == "s" ]]; then
    echo "URL do phpMyAdmin: ${APP_URL}phpmyadmin/"
  fi
  echo "Banco criado: ${APP_DB}"
  echo "Usuario do banco: ${APP_DB_USER}"
  echo "Senha do banco: (oculta)"
  echo
  echo "Status dos servicos:"
  echo " - nginx: $(systemctl is-active nginx)"
  echo " - ${PHP_FPM_SERVICE}: $(systemctl is-active "$PHP_FPM_SERVICE")"
  echo " - mysql: $(systemctl is-active mysql)"
  echo
  echo "HTTP code raiz app: ${app_code}"
  if [[ "$INSTALL_PHPMYADMIN" == "s" ]]; then
    echo "HTTP code phpMyAdmin: ${pma_code}"
  fi
  echo "============================================================"
  echo "Proximos passos:"
  echo "1) SSL com Certbot"
  echo "2) Firewall UFW"
  echo "3) Restricao phpMyAdmin por IP/BasicAuth"
  echo "4) Rotina de backups"
  echo "============================================================"
}

# ==============================
# Main
# ==============================
main() {
  require_root "$@"
  log_info "Iniciando instalacao automatizada do ERP..."

  if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    if [[ "${ID:-}" != "ubuntu" ]]; then
      log_warn "SO detectado: ${ID:-desconhecido}. Script homologado para Ubuntu 24.04."
    fi
  fi

  collect_inputs
  prepare_system
  handle_port_conflicts
  install_web_stack
  install_mysql
  configure_mysql_database
  deploy_application
  install_phpmyadmin_if_requested
  build_nginx_vhost
  import_dump_if_requested
  final_validation

  log_success "Provisionamento concluido."
}

main "$@"

