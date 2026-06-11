#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

DEFAULT_ERP_REPO_URL="https://github.com/jovem-tech/sistema-assistencia-erp.git"
DEFAULT_ERP_REPO_BRANCH="main"
DEFAULT_ERP_SOURCE_DIR="/opt/sistema-hml"
DEFAULT_STACK_NAME="sistema-hml"
DEFAULT_APP_IMAGE="sistema-hml:latest"
DEFAULT_TRAEFIK_NETWORK="traefik-public"
DEFAULT_TRAEFIK_CERTRESOLVER="letsencryptresolver"
DEFAULT_APP_TIMEZONE="America/Fortaleza"
DEFAULT_CI_ENVIRONMENT="production"
DEFAULT_APP_FORCE_HTTPS="true"
DEFAULT_APP_CSP_ENABLED="false"
DEFAULT_LOGGER_THRESHOLD="4"
DEFAULT_RUN_MIGRATIONS="true"
DEFAULT_WAIT_FOR_DB="true"
DEFAULT_CLEAR_CACHE_ON_BOOT="true"

log_info() {
    printf '[INFO] %s\n' "$*"
}

log_warn() {
    printf '[WARN] %s\n' "$*" >&2
}

log_error() {
    printf '[ERRO] %s\n' "$*" >&2
}

on_error() {
    log_error "Falha na linha ${1}: ${2}"
}

trap 'on_error "$LINENO" "$BASH_COMMAND"' ERR

require_cmd() {
    local cmd="$1"
    if ! command -v "$cmd" >/dev/null 2>&1; then
        log_error "Comando obrigatorio nao encontrado: ${cmd}"
        exit 1
    fi
}

prompt_non_empty() {
    local var_name="$1"
    local prompt="$2"
    local default_value="${3:-}"
    local value=""

    while true; do
        if [[ -n "$default_value" ]]; then
            read -r -p "${prompt} [${default_value}]: " value
            value="${value:-$default_value}"
        else
            read -r -p "${prompt}: " value
        fi

        if [[ -n "$value" ]]; then
            printf -v "$var_name" '%s' "$value"
            return 0
        fi

        log_warn "Valor obrigatorio."
    done
}

prompt_secret_with_default() {
    local var_name="$1"
    local prompt="$2"
    local default_value="${3:-}"
    local first=""
    local second=""

    while true; do
        if [[ -n "$default_value" ]]; then
            read -r -s -p "${prompt} [Enter para manter o valor atual]: " first
            echo
            if [[ -z "$first" ]]; then
                printf -v "$var_name" '%s' "$default_value"
                return 0
            fi
        else
            read -r -s -p "${prompt}: " first
            echo
            if [[ -z "$first" ]]; then
                log_warn "Valor obrigatorio."
                continue
            fi
        fi

        read -r -s -p "Confirme o valor informado: " second
        echo

        if [[ "$first" != "$second" ]]; then
            log_warn "Os valores nao conferem. Tente novamente."
            continue
        fi

        printf -v "$var_name" '%s' "$first"
        return 0
    done
}

slugify() {
    printf '%s' "$1" \
        | tr '[:upper:]' '[:lower:]' \
        | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//'
}

write_env_line() {
    local key="$1"
    local value="$2"
    printf '%s=%q\n' "$key" "$value"
}

ensure_runtime_prerequisites() {
    require_cmd curl
    require_cmd docker
    require_cmd git
    require_cmd openssl

    if ! docker info >/dev/null 2>&1; then
        log_error "Docker nao esta acessivel para o usuario atual."
        exit 1
    fi

    local swarm_state
    swarm_state="$(docker info --format '{{.Swarm.LocalNodeState}}' 2>/dev/null || true)"
    if [[ "$swarm_state" != "active" ]]; then
        log_error "Docker Swarm nao esta ativo. Instale primeiro Traefik/Portainer no Setup Vem Fazer."
        exit 1
    fi
}

load_existing_defaults() {
    local env_file_candidate="${ERP_SOURCE_DIR}/docker/swarm/setup-vemfazer.env"
    if [[ -f "$env_file_candidate" ]]; then
        log_info "Carregando configuracao existente de ${env_file_candidate}."
        set -a
        # shellcheck disable=SC1090
        source "$env_file_candidate"
        set +a
    fi
}

collect_inputs() {
    ERP_REPO_URL="${ERP_REPO_URL:-$DEFAULT_ERP_REPO_URL}"
    ERP_REPO_BRANCH="${ERP_REPO_BRANCH:-$DEFAULT_ERP_REPO_BRANCH}"
    ERP_SOURCE_DIR="${ERP_SOURCE_DIR:-$DEFAULT_ERP_SOURCE_DIR}"

    load_existing_defaults

    STACK_NAME="${STACK_NAME:-$DEFAULT_STACK_NAME}"
    STACK_SLUG="${STACK_SLUG:-$(slugify "$STACK_NAME")}"
    APP_IMAGE="${APP_IMAGE:-$DEFAULT_APP_IMAGE}"
    TRAEFIK_NETWORK="${TRAEFIK_NETWORK:-$DEFAULT_TRAEFIK_NETWORK}"
    TRAEFIK_CERTRESOLVER="${TRAEFIK_CERTRESOLVER:-$DEFAULT_TRAEFIK_CERTRESOLVER}"
    APP_TIMEZONE="${APP_TIMEZONE:-$DEFAULT_APP_TIMEZONE}"
    CI_ENVIRONMENT="${CI_ENVIRONMENT:-$DEFAULT_CI_ENVIRONMENT}"
    APP_FORCE_HTTPS="${APP_FORCE_HTTPS:-$DEFAULT_APP_FORCE_HTTPS}"
    APP_CSP_ENABLED="${APP_CSP_ENABLED:-$DEFAULT_APP_CSP_ENABLED}"
    LOGGER_THRESHOLD="${LOGGER_THRESHOLD:-$DEFAULT_LOGGER_THRESHOLD}"
    RUN_MIGRATIONS="${RUN_MIGRATIONS:-$DEFAULT_RUN_MIGRATIONS}"
    WAIT_FOR_DB="${WAIT_FOR_DB:-$DEFAULT_WAIT_FOR_DB}"
    CLEAR_CACHE_ON_BOOT="${CLEAR_CACHE_ON_BOOT:-$DEFAULT_CLEAR_CACHE_ON_BOOT}"
    MYSQL_DATABASE="${MYSQL_DATABASE:-sistema_hml}"
    MYSQL_USER="${MYSQL_USER:-sistema_hml}"
    APP_ENCRYPTION_KEY="${APP_ENCRYPTION_KEY:-hex2bin:$(openssl rand -hex 16)}"
    LEGACY_DATABASE_PORT="${LEGACY_DATABASE_PORT:-3306}"

    log_info "Coletando parametros da integracao do ERP com o Setup Vem Fazer..."

    prompt_non_empty ERP_REPO_URL "URL do repositorio do ERP" "$ERP_REPO_URL"
    prompt_non_empty ERP_REPO_BRANCH "Branch do ERP" "$ERP_REPO_BRANCH"
    prompt_non_empty ERP_SOURCE_DIR "Diretorio local do checkout do ERP" "$ERP_SOURCE_DIR"
    prompt_non_empty STACK_NAME "Nome da stack Docker Swarm" "$STACK_NAME"
    prompt_non_empty APP_IMAGE "Nome/tag da imagem Docker" "$APP_IMAGE"
    prompt_non_empty APP_HOST "Dominio publico do ERP"
    prompt_non_empty TRAEFIK_NETWORK "Rede externa usada pelo Traefik do Setup Vem Fazer" "$TRAEFIK_NETWORK"
    prompt_non_empty TRAEFIK_CERTRESOLVER "Nome do certresolver do Traefik" "$TRAEFIK_CERTRESOLVER"
    prompt_non_empty MYSQL_DATABASE "Nome do banco MySQL" "$MYSQL_DATABASE"
    prompt_non_empty MYSQL_USER "Usuario do banco MySQL" "$MYSQL_USER"
    prompt_secret_with_default MYSQL_PASSWORD "Senha do banco MySQL" "${MYSQL_PASSWORD:-}"
    prompt_secret_with_default MYSQL_ROOT_PASSWORD "Senha root do MySQL do stack" "${MYSQL_ROOT_PASSWORD:-}"
    prompt_non_empty APP_ENCRYPTION_KEY "Chave de criptografia do ERP" "$APP_ENCRYPTION_KEY"

    STACK_SLUG="$(slugify "$STACK_NAME")"
    if [[ -z "$STACK_SLUG" ]]; then
        log_error "Nao foi possivel derivar um slug valido a partir do nome da stack."
        exit 1
    fi

    LEGACY_DATABASE_HOST="${LEGACY_DATABASE_HOST:-db}"
    LEGACY_DATABASE_NAME="${LEGACY_DATABASE_NAME:-$MYSQL_DATABASE}"
    LEGACY_DATABASE_USER="${LEGACY_DATABASE_USER:-$MYSQL_USER}"
    LEGACY_DATABASE_PASSWORD="${LEGACY_DATABASE_PASSWORD:-$MYSQL_PASSWORD}"

    if [[ ! "$MYSQL_DATABASE" =~ ^[A-Za-z0-9_]+$ ]]; then
        log_error "MYSQL_DATABASE invalido. Use apenas letras, numeros e underscore."
        exit 1
    fi

    if [[ ! "$MYSQL_USER" =~ ^[A-Za-z0-9_]+$ ]]; then
        log_error "MYSQL_USER invalido. Use apenas letras, numeros e underscore."
        exit 1
    fi

    if [[ ! "$APP_HOST" =~ ^[A-Za-z0-9._-]+$ ]]; then
        log_error "APP_HOST invalido. Informe apenas dominio ou IP."
        exit 1
    fi

    log_info "Resumo rapido:"
    printf '  - Repo: %s (%s)\n' "$ERP_REPO_URL" "$ERP_REPO_BRANCH"
    printf '  - Checkout: %s\n' "$ERP_SOURCE_DIR"
    printf '  - Stack: %s\n' "$STACK_NAME"
    printf '  - Dominio: https://%s\n' "$APP_HOST"
    printf '  - Rede Traefik: %s\n' "$TRAEFIK_NETWORK"
    printf '  - Certresolver: %s\n' "$TRAEFIK_CERTRESOLVER"
}

prepare_source_checkout() {
    if [[ -d "${ERP_SOURCE_DIR}/.git" ]]; then
        log_info "Repositorio existente detectado em ${ERP_SOURCE_DIR}. Atualizando checkout."
        git -C "$ERP_SOURCE_DIR" fetch --all --prune
        git -C "$ERP_SOURCE_DIR" checkout "$ERP_REPO_BRANCH"
        git -C "$ERP_SOURCE_DIR" pull --ff-only origin "$ERP_REPO_BRANCH"
    else
        if [[ -d "$ERP_SOURCE_DIR" ]] && [[ -n "$(find "$ERP_SOURCE_DIR" -mindepth 1 -maxdepth 1 2>/dev/null)" ]]; then
            log_error "Diretorio ${ERP_SOURCE_DIR} nao esta vazio e nao e um repositorio Git."
            exit 1
        fi

        mkdir -p "$(dirname "$ERP_SOURCE_DIR")"
        rm -rf "$ERP_SOURCE_DIR"
        log_info "Clonando repositorio do ERP em ${ERP_SOURCE_DIR}."
        git clone --branch "$ERP_REPO_BRANCH" --depth 1 "$ERP_REPO_URL" "$ERP_SOURCE_DIR"
    fi
}

validate_vemfazer_stack_files() {
    STACK_FILE="${ERP_SOURCE_DIR}/docker/swarm/setup-vemfazer-stack.yml"
    ENV_FILE="${ERP_SOURCE_DIR}/docker/swarm/setup-vemfazer.env"

    if [[ ! -f "$STACK_FILE" ]]; then
        log_error "Stack file nao encontrado: ${STACK_FILE}"
        exit 1
    fi

    if ! docker network inspect "$TRAEFIK_NETWORK" >/dev/null 2>&1; then
        log_error "Rede externa do Traefik nao encontrada: ${TRAEFIK_NETWORK}"
        log_warn "Use exatamente o mesmo nome de rede configurado no Setup Vem Fazer."
        exit 1
    fi
}

write_stack_env_file() {
    mkdir -p "$(dirname "$ENV_FILE")"

    {
        write_env_line "ERP_REPO_URL" "$ERP_REPO_URL"
        write_env_line "ERP_REPO_BRANCH" "$ERP_REPO_BRANCH"
        write_env_line "ERP_SOURCE_DIR" "$ERP_SOURCE_DIR"
        write_env_line "STACK_NAME" "$STACK_NAME"
        write_env_line "STACK_SLUG" "$STACK_SLUG"
        write_env_line "APP_IMAGE" "$APP_IMAGE"
        write_env_line "APP_HOST" "$APP_HOST"
        write_env_line "TRAEFIK_NETWORK" "$TRAEFIK_NETWORK"
        write_env_line "TRAEFIK_CERTRESOLVER" "$TRAEFIK_CERTRESOLVER"
        write_env_line "APP_TIMEZONE" "$APP_TIMEZONE"
        write_env_line "CI_ENVIRONMENT" "$CI_ENVIRONMENT"
        write_env_line "APP_FORCE_HTTPS" "$APP_FORCE_HTTPS"
        write_env_line "APP_CSP_ENABLED" "$APP_CSP_ENABLED"
        write_env_line "LOGGER_THRESHOLD" "$LOGGER_THRESHOLD"
        write_env_line "APP_ENCRYPTION_KEY" "$APP_ENCRYPTION_KEY"
        write_env_line "MYSQL_DATABASE" "$MYSQL_DATABASE"
        write_env_line "MYSQL_USER" "$MYSQL_USER"
        write_env_line "MYSQL_PASSWORD" "$MYSQL_PASSWORD"
        write_env_line "MYSQL_ROOT_PASSWORD" "$MYSQL_ROOT_PASSWORD"
        write_env_line "LEGACY_DATABASE_HOST" "$LEGACY_DATABASE_HOST"
        write_env_line "LEGACY_DATABASE_PORT" "$LEGACY_DATABASE_PORT"
        write_env_line "LEGACY_DATABASE_NAME" "$LEGACY_DATABASE_NAME"
        write_env_line "LEGACY_DATABASE_USER" "$LEGACY_DATABASE_USER"
        write_env_line "LEGACY_DATABASE_PASSWORD" "$LEGACY_DATABASE_PASSWORD"
        write_env_line "RUN_MIGRATIONS" "$RUN_MIGRATIONS"
        write_env_line "WAIT_FOR_DB" "$WAIT_FOR_DB"
        write_env_line "CLEAR_CACHE_ON_BOOT" "$CLEAR_CACHE_ON_BOOT"
    } > "$ENV_FILE"

    chmod 600 "$ENV_FILE"
    log_info "Arquivo de ambiente salvo em ${ENV_FILE}."
}

build_application_image() {
    log_info "Buildando imagem Docker ${APP_IMAGE}..."
    docker build -t "$APP_IMAGE" "$ERP_SOURCE_DIR"
}

deploy_stack() {
    log_info "Publicando stack ${STACK_NAME}..."
    (
        set -a
        # shellcheck disable=SC1090
        source "$ENV_FILE"
        set +a
        cd "$ERP_SOURCE_DIR"
        docker stack deploy -c "$STACK_FILE" "$STACK_NAME"
    )
}

wait_for_service() {
    local service_name="$1"
    local attempts="${2:-40}"
    local interval="${3:-15}"
    local expected="${STACK_NAME}_${service_name}"
    local replicas=""

    for ((i = 1; i <= attempts; i++)); do
        replicas="$(docker service ls --filter "name=${expected}" --format '{{.Replicas}}' | head -n 1 || true)"
        if [[ "$replicas" == "1/1" ]]; then
            log_info "Servico ${expected} esta online."
            return 0
        fi
        sleep "$interval"
    done

    log_error "Servico ${expected} nao atingiu 1/1."
    docker service ps "$expected" || true
    return 1
}

save_install_metadata() {
    local data_dir="/root/dados_vps"
    local data_file="${data_dir}/dados_sistema_hml"

    if [[ ! -d "$data_dir" ]]; then
        return 0
    fi

    cat > "$data_file" <<EOF
[ SISTEMA HML ]

Dominio: https://${APP_HOST}

Stack: ${STACK_NAME}

Imagem: ${APP_IMAGE}

Repositorio: ${ERP_REPO_URL}

Branch: ${ERP_REPO_BRANCH}

Diretorio do checkout: ${ERP_SOURCE_DIR}

Arquivo de ambiente: ${ENV_FILE}

Arquivo de stack: ${STACK_FILE}

Banco de dados: ${MYSQL_DATABASE}

Usuario do banco: ${MYSQL_USER}

Resolver Traefik: ${TRAEFIK_CERTRESOLVER}

Rede do Traefik: ${TRAEFIK_NETWORK}
EOF

    chmod 600 "$data_file"
}

print_summary() {
    local app_status="indisponivel"
    app_status="$(curl -k -s -o /dev/null -w '%{http_code}' --max-time 20 "https://${APP_HOST}/login" || true)"

    printf '\n'
    printf '============================================================\n'
    printf 'ERP publicado no ecossistema Setup Vem Fazer\n'
    printf '============================================================\n'
    printf 'URL do ERP: https://%s\n' "$APP_HOST"
    printf 'Stack: %s\n' "$STACK_NAME"
    printf 'Imagem: %s\n' "$APP_IMAGE"
    printf 'Checkout: %s\n' "$ERP_SOURCE_DIR"
    printf 'Env do stack: %s\n' "$ENV_FILE"
    printf 'HTTP /login: %s\n' "${app_status:-indisponivel}"
    printf '\n'
    docker stack services "$STACK_NAME"
    printf '============================================================\n'
}

main() {
    ensure_runtime_prerequisites
    collect_inputs
    prepare_source_checkout
    validate_vemfazer_stack_files
    write_stack_env_file
    build_application_image
    deploy_stack
    wait_for_service "db"
    wait_for_service "app"
    save_install_metadata
    print_summary
}

main "$@"
