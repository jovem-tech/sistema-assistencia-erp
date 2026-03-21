#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
ERP_DIR="${1:-$ROOT_DIR}"
GATEWAY_DIR="${2:-$ROOT_DIR/whatsapp-api}"
GATEWAY_SERVICE="${3:-whatsapp-gateway}"

echo "Instalador VPS - Sistema de Assistencia Tecnica"
echo "ERP dir      : ${ERP_DIR}"
echo "Gateway dir  : ${GATEWAY_DIR}"
echo "Gateway svc  : ${GATEWAY_SERVICE}"
echo

cd "${ERP_DIR}"

echo "[1/4] Rodando migracoes do ERP..."
php spark migrate

echo "[2/4] Limpando cache de framework..."
php spark cache:clear || true

echo "[3/4] Permissoes basicas de escrita..."
mkdir -p writable public/uploads
chmod -R 775 writable public/uploads || true

echo "[4/4] Opcao de instalacao do WhatsApp Gateway"
read -r -p "Instalar WhatsApp Gateway agora? [s/N]: " INSTALL_GATEWAY
if [[ "${INSTALL_GATEWAY,,}" == "s" || "${INSTALL_GATEWAY,,}" == "sim" || "${INSTALL_GATEWAY,,}" == "y" || "${INSTALL_GATEWAY,,}" == "yes" ]]; then
  if [[ ! -f "${GATEWAY_DIR}/install-whatsapp-api.sh" ]]; then
    echo "ERRO: script ${GATEWAY_DIR}/install-whatsapp-api.sh nao encontrado."
    exit 1
  fi
  chmod +x "${GATEWAY_DIR}/install-whatsapp-api.sh"
  "${GATEWAY_DIR}/install-whatsapp-api.sh" "${GATEWAY_DIR}" "${GATEWAY_SERVICE}"
else
  echo "Gateway nao instalado por opcao do operador."
fi

echo
echo "Instalacao base concluida."
echo "Proximo passo: configurar Integracoes WhatsApp no ERP."
