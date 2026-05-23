#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
STACK_FILE="${ROOT_DIR}/docker/swarm/contabo-stack.yml"
ENV_FILE="${1:-${ROOT_DIR}/docker/swarm/contabo.env}"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Arquivo de ambiente nao encontrado: $ENV_FILE" >&2
    echo "Copie docker/swarm/contabo.env.example para docker/swarm/contabo.env e ajuste as credenciais." >&2
    exit 1
fi

set -a
source "$ENV_FILE"
set +a

APP_IMAGE="${APP_IMAGE:-sistema-hml:latest}"
STACK_NAME="${STACK_NAME:-sistema-hml}"

echo "[deploy] Buildando imagem ${APP_IMAGE}..."
docker build -t "${APP_IMAGE}" "${ROOT_DIR}"

echo "[deploy] Publicando stack ${STACK_NAME}..."
docker stack deploy -c "${STACK_FILE}" "${STACK_NAME}"

echo "[deploy] Servicos publicados:"
docker stack services "${STACK_NAME}"
