#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/sistema/whatsapp-api}"
SERVICE_NAME="${2:-whatsapp-gateway}"

echo "[1/7] Atualizando pacotes..."
sudo apt-get update -y

echo "[2/7] Instalando dependencias do Chromium/Puppeteer..."
sudo apt-get install -y \
  ca-certificates curl gnupg lsb-release \
  libatk1.0-0 libatk-bridge2.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 \
  libfontconfig1 libgcc1 libgdk-pixbuf2.0-0 libglib2.0-0 \
  libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 \
  libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 \
  libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 \
  xdg-utils fonts-liberation libgbm1 libgbm-dev

echo "[2.1/7] Instalando libs de audio (fallback automatico)..."
sudo apt-get install -y libasound2t64 || sudo apt-get install -y libasound2 || true

if ! command -v node >/dev/null 2>&1; then
  echo "[3/7] Instalando Node.js 20.x..."
  curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
  sudo apt-get install -y nodejs
else
  echo "[3/7] Node.js ja instalado: $(node -v)"
fi

echo "[4/7] Instalando PM2 globalmente..."
sudo npm install -g pm2

echo "[5/7] Instalando dependencias NPM do gateway..."
cd "${APP_DIR}"
npm install --omit=dev

echo "[6/7] Iniciando servico no PM2..."
pm2 delete "${SERVICE_NAME}" >/dev/null 2>&1 || true
pm2 start server.js --name "${SERVICE_NAME}"
pm2 save

echo "[7/7] Habilitando boot automatico do PM2..."
pm2 startup systemd -u "${USER}" --hp "${HOME}" | sed 's/^/[pm2-startup] /'

echo
echo "Instalacao concluida."
echo "Servico PM2: ${SERVICE_NAME}"
echo "Diretorio: ${APP_DIR}"
echo "Verifique status: pm2 status ${SERVICE_NAME}"
