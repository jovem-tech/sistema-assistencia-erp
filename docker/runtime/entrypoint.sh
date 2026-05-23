#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

bool_value() {
    local raw="${1:-false}"
    raw="$(printf '%s' "$raw" | tr '[:upper:]' '[:lower:]')"
    case "$raw" in
        1|true|yes|on) printf 'true' ;;
        *) printf 'false' ;;
    esac
}

quote_value() {
    printf "%s" "${1:-}" | sed "s/'/'\\\\''/g"
}

ensure_trailing_slash() {
    local value="${1:-}"
    if [[ -z "$value" ]]; then
        printf '/'
        return
    fi

    case "$value" in
        */) printf '%s' "$value" ;;
        *) printf '%s/' "$value" ;;
    esac
}

wait_for_database() {
    local retries="${DB_WAIT_RETRIES:-30}"
    local sleep_seconds="${DB_WAIT_SLEEP_SECONDS:-5}"
    local attempt=1

    until php -r '
        mysqli_report(MYSQLI_REPORT_OFF);
        $host = getenv("DATABASE_HOST") ?: "db";
        $port = (int) (getenv("DATABASE_PORT") ?: 3306);
        $user = getenv("DATABASE_USER") ?: "sistema_hml";
        $pass = getenv("DATABASE_PASSWORD") ?: "";
        $name = getenv("DATABASE_NAME") ?: "sistema_hml";
        $db = @new mysqli($host, $user, $pass, $name, $port);
        if ($db->connect_errno) {
            fwrite(STDERR, $db->connect_error . PHP_EOL);
            exit(1);
        }
        $db->close();
    '; do
        if (( attempt >= retries )); then
            echo "[docker-entrypoint] Banco indisponivel apos ${attempt} tentativas." >&2
            return 1
        fi

        echo "[docker-entrypoint] Aguardando banco (${attempt}/${retries})..." >&2
        attempt=$((attempt + 1))
        sleep "${sleep_seconds}"
    done

    return 0
}

APP_BASE_URL="$(ensure_trailing_slash "${APP_BASE_URL:-http://localhost/}")"
CI_ENVIRONMENT="${CI_ENVIRONMENT:-production}"
APP_FORCE_HTTPS="$(bool_value "${APP_FORCE_HTTPS:-true}")"
APP_CSP_ENABLED="$(bool_value "${APP_CSP_ENABLED:-false}")"
APP_TIMEZONE="${APP_TIMEZONE:-America/Fortaleza}"
SESSION_DRIVER="${SESSION_DRIVER:-CodeIgniter\\Session\\Handlers\\FileHandler}"
LOGGER_THRESHOLD="${LOGGER_THRESHOLD:-4}"
DATABASE_HOST="${DATABASE_HOST:-db}"
DATABASE_PORT="${DATABASE_PORT:-3306}"
DATABASE_NAME="${DATABASE_NAME:-sistema_hml}"
DATABASE_USER="${DATABASE_USER:-sistema_hml}"
DATABASE_PASSWORD="${DATABASE_PASSWORD:-}"
LEGACY_DATABASE_HOST="${LEGACY_DATABASE_HOST:-$DATABASE_HOST}"
LEGACY_DATABASE_PORT="${LEGACY_DATABASE_PORT:-$DATABASE_PORT}"
LEGACY_DATABASE_NAME="${LEGACY_DATABASE_NAME:-$DATABASE_NAME}"
LEGACY_DATABASE_USER="${LEGACY_DATABASE_USER:-$DATABASE_USER}"
LEGACY_DATABASE_PASSWORD="${LEGACY_DATABASE_PASSWORD:-$DATABASE_PASSWORD}"
ENCRYPTION_KEY="${ENCRYPTION_KEY:-}"
RUN_MIGRATIONS="$(bool_value "${RUN_MIGRATIONS:-true}")"
WAIT_FOR_DB="$(bool_value "${WAIT_FOR_DB:-true}")"
CLEAR_CACHE_ON_BOOT="$(bool_value "${CLEAR_CACHE_ON_BOOT:-true}")"

if [[ -z "$ENCRYPTION_KEY" ]]; then
    ENCRYPTION_KEY="hex2bin:$(php -r 'echo bin2hex(random_bytes(16));')"
fi

cat > .env <<EOF
CI_ENVIRONMENT = $(quote_value "$CI_ENVIRONMENT")

app.baseURL = '$(quote_value "$APP_BASE_URL")'
app.indexPage = ''
app.forceGlobalSecureRequests = ${APP_FORCE_HTTPS}
app.CSPEnabled = ${APP_CSP_ENABLED}
app.appTimezone = '$(quote_value "$APP_TIMEZONE")'
app.defaultLocale = 'pt-BR'

database.default.hostname = '$(quote_value "$DATABASE_HOST")'
database.default.database = '$(quote_value "$DATABASE_NAME")'
database.default.username = '$(quote_value "$DATABASE_USER")'
database.default.password = '$(quote_value "$DATABASE_PASSWORD")'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = $(quote_value "$DATABASE_PORT")
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci

database.legacy.hostname = '$(quote_value "$LEGACY_DATABASE_HOST")'
database.legacy.database = '$(quote_value "$LEGACY_DATABASE_NAME")'
database.legacy.username = '$(quote_value "$LEGACY_DATABASE_USER")'
database.legacy.password = '$(quote_value "$LEGACY_DATABASE_PASSWORD")'
database.legacy.DBDriver = MySQLi
database.legacy.DBPrefix =
database.legacy.port = $(quote_value "$LEGACY_DATABASE_PORT")
database.legacy.charset = utf8mb4
database.legacy.DBCollat = utf8mb4_unicode_ci

legacyImport.sourceName = erp
legacyImport.batchSize = 250
legacyImport.allowCatalogAutoCreate = true
legacyImport.writeInitialStatusHistory = true

session.driver = '$(quote_value "$SESSION_DRIVER")'
session.savePath = '/var/www/html/writable/session'

encryption.key = $(quote_value "$ENCRYPTION_KEY")

logger.threshold = $(quote_value "$LOGGER_THRESHOLD")
EOF

mkdir -p \
    public/uploads \
    writable/cache \
    writable/debugbar \
    writable/logs \
    writable/session \
    writable/uploads

chown -R www-data:www-data public/uploads writable
find writable -type d -exec chmod 775 {} \;
find writable -type f -exec chmod 664 {} \;
find public/uploads -type d -exec chmod 775 {} \;
find public/uploads -type f -exec chmod 664 {} \;

if [[ "$WAIT_FOR_DB" == "true" || "$RUN_MIGRATIONS" == "true" ]]; then
    wait_for_database
fi

if [[ "$RUN_MIGRATIONS" == "true" ]]; then
    php spark migrate --all --no-header
fi

if [[ "$CLEAR_CACHE_ON_BOOT" == "true" ]]; then
    php spark cache:clear || true
fi

exec "$@"

