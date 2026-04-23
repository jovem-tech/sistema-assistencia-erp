require('dotenv').config();

const fs = require('fs');
const path = require('path');
const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcodeTerminal = require('qrcode-terminal');
const QRCode = require('qrcode');

const app = express();

const config = {
    host: process.env.HOST || '0.0.0.0',
    port: Number.parseInt(process.env.PORT || '3001', 10),
    nodeEnv: (process.env.NODE_ENV || 'development').toLowerCase(),
    apiToken: (process.env.API_TOKEN || '').trim(),
    erpOrigin: (process.env.ERP_ORIGIN || '').trim(),
    sessionPath: (process.env.WHATSAPP_SESSION_PATH || process.env.SESSION_PATH || './.wwebjs_auth').trim(),
    clientId: (process.env.WHATSAPP_CLIENT_ID || 'erp-local-gateway').trim(),
    reconnectDelayMs: Number.parseInt(process.env.RECONNECT_DELAY_MS || '5000', 10),
    rateLimitWindowMs: Number.parseInt(process.env.RATE_LIMIT_WINDOW_MS || '60000', 10),
    rateLimitMax: Number.parseInt(process.env.RATE_LIMIT_MAX || '120', 10),
    requestTimeoutMs: Number.parseInt(process.env.REQUEST_TIMEOUT_MS || '30000', 10),
    erpWebhookUrl: (process.env.ERP_WEBHOOK_URL || '').trim(),
    erpWebhookToken: (process.env.ERP_WEBHOOK_TOKEN || '').trim(),
    erpWebhookTimeoutMs: Number.parseInt(process.env.ERP_WEBHOOK_TIMEOUT_MS || '10000', 10),
    forwardInboundEnabled: !['0', 'false', 'no', 'off'].includes((process.env.FORWARD_INBOUND_ENABLED || '1').toLowerCase().trim()),
    forwardInboundMediaEnabled: !['0', 'false', 'no', 'off'].includes((process.env.FORWARD_INBOUND_MEDIA_ENABLED || '1').toLowerCase().trim()),
    inboundMediaMaxBytes: Number.parseInt(process.env.INBOUND_MEDIA_MAX_BYTES || '5242880', 10), // 5MB
    allowedMime: [
        'jpg', 'jpeg', 'png', 'webp', 'gif',
        'pdf',
        'mp4', 'webm', 'mov', 'mkv',
        'mp3', 'ogg', 'wav', 'm4a', 'aac', 'opus',
        'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar'
    ],
    chromeExecutablePath: (process.env.CHROME_EXECUTABLE_PATH || '').trim(),
    logsDir: (process.env.LOGS_DIR || path.resolve(__dirname, 'logs')).trim()
};

const startedAt = Date.now();
const allowedOrigins = config.erpOrigin
    .split(',')
    .map((value) => value.trim())
    .filter((value) => value.length > 0);

if (!fs.existsSync(config.logsDir)) {
    fs.mkdirSync(config.logsDir, { recursive: true });
}

const gatewayLogFile = path.join(config.logsDir, 'gateway.log');

function writeLog(level, message, context = null) {
    const ts = new Date().toISOString();
    const suffix = context ? ` ${JSON.stringify(context)}` : '';
    const line = `[${ts}] [${level}] ${message}${suffix}`;
    try {
        fs.appendFileSync(gatewayLogFile, `${line}\n`);
    } catch (_) {
        // nao interrompe execucao por falha de log
    }
    if (level === 'ERROR') {
        console.error(line);
        return;
    }
    if (level === 'WARN') {
        console.warn(line);
        return;
    }
    console.log(line);
}

let client = null;
let isInitializing = false;
let restartTimeout = null;
let lastQrDataUrl = null;
let lastQrAt = null;
let state = {
    status: 'starting',
    ready: false,
    hasQr: false,
    lastReadyAt: null,
    lastDisconnectAt: null,
    lastErrorAt: null,
    lastErrorMessage: null,
    account: {
        number: null,
        pushname: null,
        platform: null
    }
};

app.set('trust proxy', 1);
app.use(helmet({
    crossOriginResourcePolicy: false
}));
app.use(cors({
    origin: (origin, callback) => {
        if (!origin || allowedOrigins.length === 0 || allowedOrigins.includes(origin)) {
            callback(null, true);
            return;
        }
        callback(new Error('Origin not allowed by ERP_ORIGIN.'));
    },
    methods: ['GET', 'POST', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Accept', 'Authorization', 'X-Api-Token', 'X-ERP-Origin']
}));
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));
app.use((req, res, next) => {
    req.setTimeout(config.requestTimeoutMs);
    next();
});

const sensitiveLimiter = rateLimit({
    windowMs: config.rateLimitWindowMs,
    max: config.rateLimitMax,
    standardHeaders: true,
    legacyHeaders: false,
    message: {
        success: false,
        status: 'rate_limited',
        message: 'Limite de requisicoes excedido para o gateway local.',
        error: {
            code: 'RATE_LIMITED'
        }
    }
});

function jsonSuccess(res, status, message, data = {}, httpCode = 200) {
    return res.status(httpCode).json({
        success: true,
        status,
        message,
        data
    });
}

function jsonError(res, status, message, error = {}, httpCode = 400) {
    return res.status(httpCode).json({
        success: false,
        status,
        message,
        error
    });
}

function getRequestToken(req) {
    const byHeader = (req.headers['x-api-token'] || '').toString().trim();
    if (byHeader !== '') {
        return byHeader;
    }

    const auth = (req.headers.authorization || '').toString().trim();
    if (auth.toLowerCase().startsWith('bearer ')) {
        return auth.substring(7).trim();
    }

    return '';
}

function getRequestOrigin(req) {
    const customOrigin = (req.headers['x-erp-origin'] || '').toString().trim();
    if (customOrigin !== '') {
        return customOrigin;
    }

    const origin = (req.headers.origin || '').toString().trim();
    if (origin !== '') {
        return origin;
    }

    const referer = (req.headers.referer || '').toString().trim();
    if (referer !== '') {
        try {
            const parsed = new URL(referer);
            return `${parsed.protocol}//${parsed.host}`;
        } catch (error) {
            return '';
        }
    }

    return '';
}

function isOriginAllowed(origin) {
    if (allowedOrigins.length === 0) {
        return true;
    }
    if (origin === '') {
        return false;
    }
    return allowedOrigins.includes(origin);
}

function requireGatewayAuth(req, res, next) {
    if (config.apiToken !== '') {
        const requestToken = getRequestToken(req);
        if (requestToken === '' || requestToken !== config.apiToken) {
            return jsonError(
                res,
                'unauthorized',
                'Token de autenticacao invalido para o gateway local.',
                { code: 'INVALID_API_TOKEN' },
                401
            );
        }
    } else if (config.nodeEnv === 'production') {
        return jsonError(
            res,
            'misconfigured',
            'API_TOKEN nao configurado em producao.',
            { code: 'MISSING_API_TOKEN' },
            500
        );
    }

    const reqOrigin = getRequestOrigin(req);
    if (!isOriginAllowed(reqOrigin)) {
        return jsonError(
            res,
            'forbidden_origin',
            'Origem nao autorizada para consumir o gateway local.',
            { code: 'ORIGIN_NOT_ALLOWED', origin: reqOrigin },
            403
        );
    }

    return next();
}

function toChatId(phone) {
    const digits = String(phone || '').replace(/\D/g, '');
    return digits.endsWith('@c.us') ? digits : `${digits}@c.us`;
}

function mimeFromFormat(format) {
    const normalized = String(format || '').toLowerCase().replace('.', '').trim();
    if (normalized === 'pdf') return 'application/pdf';
    if (['jpg', 'jpeg', 'png', 'webp'].includes(normalized)) return `image/${normalized === 'jpg' ? 'jpeg' : normalized}`;
    if (normalized === 'gif') return 'image/gif';
    if (normalized === 'mp4') return 'video/mp4';
    if (normalized === 'webm') return 'video/webm';
    if (normalized === 'mov') return 'video/quicktime';
    if (normalized === 'mkv') return 'video/x-matroska';
    if (normalized === 'mp3') return 'audio/mpeg';
    if (normalized === 'ogg') return 'audio/ogg';
    if (normalized === 'wav') return 'audio/wav';
    if (normalized === 'm4a') return 'audio/mp4';
    if (normalized === 'aac') return 'audio/aac';
    if (normalized === 'opus') return 'audio/opus';
    if (normalized === 'txt') return 'text/plain';
    if (normalized === 'csv') return 'text/csv';
    if (normalized === 'doc') return 'application/msword';
    if (normalized === 'docx') return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    if (normalized === 'xls') return 'application/vnd.ms-excel';
    if (normalized === 'xlsx') return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    if (normalized === 'zip') return 'application/zip';
    if (normalized === 'rar') return 'application/vnd.rar';
    return 'application/octet-stream';
}

function base64Body(raw) {
    const value = String(raw || '').trim();
    if (!value) return '';

    const comma = value.indexOf(',');
    if (value.startsWith('data:') && comma > -1) {
        return value.substring(comma + 1);
    }
    return value;
}

function sanitizeFileName(value, fallback = 'arquivo.bin') {
    const name = String(value || '').trim();
    if (!name) return fallback;
    return name.replace(/[^\w.\-]/g, '_');
}

function extensionFromMime(mimeType = '') {
    const mime = String(mimeType || '').toLowerCase().trim();
    if (!mime) return 'bin';
    if (mime.includes('jpeg')) return 'jpg';
    if (mime.includes('png')) return 'png';
    if (mime.includes('webp')) return 'webp';
    if (mime.includes('gif')) return 'gif';
    if (mime.includes('pdf')) return 'pdf';
    if (mime.includes('quicktime')) return 'mov';
    if (mime.includes('matroska')) return 'mkv';
    if (mime.includes('video/webm')) return 'webm';
    if (mime.includes('video/mp4')) return 'mp4';
    if (mime.includes('audio/mpeg')) return 'mp3';
    if (mime.includes('audio/ogg')) return 'ogg';
    if (mime.includes('audio/wav')) return 'wav';
    if (mime.includes('audio/mp4')) return 'm4a';
    if (mime.includes('audio/aac')) return 'aac';
    if (mime.includes('audio/opus')) return 'opus';
    if (mime.includes('text/plain')) return 'txt';
    if (mime.includes('text/csv')) return 'csv';
    if (mime.includes('spreadsheetml.sheet')) return 'xlsx';
    if (mime.includes('ms-excel')) return 'xls';
    if (mime.includes('wordprocessingml.document')) return 'docx';
    if (mime.includes('msword')) return 'doc';
    if (mime.includes('application/zip')) return 'zip';
    if (mime.includes('vnd.rar')) return 'rar';
    return 'bin';
}

async function extractMessageMediaPayload(message, options = {}) {
    const payload = {
        mediaBase64: null,
        mediaMime: null,
        mediaFilename: null,
        mediaSizeBytes: 0
    };

    if (!config.forwardInboundMediaEnabled || !message?.hasMedia) {
        return payload;
    }

    const fallbackPrefix = String(options.fallbackPrefix || 'inbound').trim() || 'inbound';
    const warnLabel = String(options.warnLabel || 'inbound').trim() || 'inbound';
    const logContext = options.logContext && typeof options.logContext === 'object'
        ? options.logContext
        : {};

    try {
        const media = await message.downloadMedia();
        if (!media || !media.data) {
            return payload;
        }

        payload.mediaBase64 = String(media.data);
        payload.mediaMime = String(media.mimetype || '').toLowerCase().trim() || null;
        payload.mediaFilename = sanitizeFileName(
            media.filename || `${fallbackPrefix}_${Date.now()}.${extensionFromMime(payload.mediaMime || '')}`
        );
        payload.mediaSizeBytes = Buffer.byteLength(payload.mediaBase64, 'base64');

        if (payload.mediaSizeBytes > config.inboundMediaMaxBytes) {
            writeLog('WARN', `Midia ${warnLabel} excede limite e sera ignorada.`, {
                mediaSizeBytes: payload.mediaSizeBytes,
                inboundMediaMaxBytes: config.inboundMediaMaxBytes,
                ...logContext
            });
            payload.mediaBase64 = null;
            payload.mediaMime = null;
            payload.mediaFilename = null;
            payload.mediaSizeBytes = 0;
        }
    } catch (mediaError) {
        writeLog('WARN', `Falha ao baixar midia ${warnLabel}.`, {
            detail: mediaError?.message || 'unknown',
            ...logContext
        });
    }

    return payload;
}

function updateState(patch = {}) {
    state = {
        ...state,
        ...patch
    };
}

function buildWebhookCandidateUrls(rawUrl) {
    const original = String(rawUrl || '').trim();
    if (!original) return [];
    const candidates = [original];

    try {
        const parsed = new URL(original);
        const host = String(parsed.hostname || '').toLowerCase().trim();
        if (host === 'localhost') {
            const alt = new URL(original);
            alt.hostname = '127.0.0.1';
            candidates.push(alt.toString());
        } else if (host === '127.0.0.1' || host === '::1') {
            const alt = new URL(original);
            alt.hostname = 'localhost';
            candidates.push(alt.toString());
        }
    } catch (_) {
        // URL invalida: mantem apenas original
    }

    return [...new Set(candidates)];
}

async function forwardInboundToErp(payload) {
    if (!config.forwardInboundEnabled) {
        return { ok: false, skipped: true, reason: 'FORWARD_INBOUND_DISABLED' };
    }
    if (!config.erpWebhookUrl) {
        return { ok: false, skipped: true, reason: 'ERP_WEBHOOK_URL_EMPTY' };
    }
    if (typeof fetch !== 'function') {
        return { ok: false, skipped: true, reason: 'FETCH_UNAVAILABLE' };
    }

    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
    if (config.erpWebhookToken) {
        headers['X-Webhook-Token'] = config.erpWebhookToken;
    }

    const attempts = [];
    const candidates = buildWebhookCandidateUrls(config.erpWebhookUrl);
    let lastFailure = null;

    for (const targetUrl of candidates) {
        const ctrl = new AbortController();
        const timeout = setTimeout(() => ctrl.abort(), Math.max(2000, config.erpWebhookTimeoutMs));
        try {
            const response = await fetch(targetUrl, {
                method: 'POST',
                headers,
                body: JSON.stringify(payload),
                signal: ctrl.signal
            });

            const text = await response.text();
            let body = null;
            try {
                body = JSON.parse(text);
            } catch (_) {
                body = { raw: text };
            }

            if (!response.ok) {
                const fail = {
                    ok: false,
                    skipped: false,
                    status: response.status,
                    body,
                    target_url: targetUrl
                };
                attempts.push(fail);
                lastFailure = fail;
                continue;
            }

            return {
                ok: true,
                skipped: false,
                status: response.status,
                body,
                target_url: targetUrl,
                attempts
            };
        } catch (error) {
            const fail = {
                ok: false,
                skipped: false,
                status: 0,
                error: error?.message || 'unknown',
                target_url: targetUrl
            };
            attempts.push(fail);
            lastFailure = fail;
        } finally {
            clearTimeout(timeout);
        }
    }

    return {
        ok: false,
        skipped: false,
        status: lastFailure?.status || 0,
        error: lastFailure?.error || null,
        body: lastFailure?.body || null,
        target_url: lastFailure?.target_url || config.erpWebhookUrl,
        attempts
    };
}

function resolveChromeExecutablePath() {
    if (config.chromeExecutablePath) {
        return config.chromeExecutablePath;
    }

    if (process.platform === 'win32') {
        const fs = require('fs');
        const candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
        ];
        for (const candidate of candidates) {
            if (fs.existsSync(candidate)) {
                return candidate;
            }
        }
    }

    return undefined;
}

function forceKillChromium() {
    return new Promise((resolve) => {
        const sessionKey = `session-${config.clientId}`;
        writeLog('INFO', `Limpando processos do Chromium relacionados a: ${sessionKey}`);
        
        if (process.platform === 'win32') {
            const { exec } = require('child_process');
            // No Windows, mata processos que tenham a nossa sessionPath no comando (chrome ou edge)
            exec(`wmic process where "commandline like '%${sessionKey}%'" delete`, () => {
                // Taskkill generico como fallback para processos zumbis sem janela
                exec('taskkill /F /FI "IMAGENAME eq chrome.exe" /FI "MEMUSAGE gt 10000" /T', () => {
                    exec('taskkill /F /FI "IMAGENAME eq msedge.exe" /FI "MEMUSAGE gt 10000" /T', () => resolve());
                });
            });
        } else {
            const { exec } = require('child_process');
            exec(`pkill -f "${sessionKey}"`, () => resolve());
        }
    });
}

function bindClientEvents(instance) {
    const runtimeProvider = config.nodeEnv === 'production' ? 'api_whats_linux' : 'api_whats_local';
    const isDirectContactId = (value) => {
        const id = String(value || '').trim().toLowerCase();
        return id.endsWith('@c.us') || id.endsWith('@s.whatsapp.net');
    };
    const normalizeContactNumber = (value) => String(value || '').replace(/@.*/g, '').replace(/\D/g, '');
    const processedEvents = new Map();

    const shouldProcessEvent = (key) => {
        const normalizedKey = String(key || '').trim();
        if (!normalizedKey) {
            return true;
        }
        const now = Date.now();
        const ttlMs = 180000;

        for (const [k, ts] of processedEvents.entries()) {
            if ((now - ts) > ttlMs) {
                processedEvents.delete(k);
            }
        }

        if (processedEvents.has(normalizedKey)) {
            return false;
        }
        processedEvents.set(normalizedKey, now);
        return true;
    };

    async function resolvePeerFromMessage(message) {
        let peerId = String(message?.to || '').trim();
        if (!isDirectContactId(peerId)) {
            const chatHint = String(message?.from || '').trim();
            if (isDirectContactId(chatHint)) {
                peerId = chatHint;
            }
        }

        if (!isDirectContactId(peerId) && typeof message?.getChat === 'function') {
            try {
                const chat = await message.getChat();
                const chatId = String(chat?.id?._serialized || chat?.id || '').trim();
                if (isDirectContactId(chatId)) {
                    peerId = chatId;
                }
            } catch (_) {
                // segue com fallback atual
            }
        }

        if (!isDirectContactId(peerId)) {
            return { peerId: '', peerNumber: '' };
        }

        return {
            peerId,
            peerNumber: normalizeContactNumber(peerId)
        };
    }

    async function handleOutboundFromMe(message, source = 'unknown') {
        if (!message || !message.fromMe) {
            return;
        }

        const messageId = String(message?.id?._serialized || '').trim();
        const dedupeKey = messageId !== ''
            ? `outbound:${messageId}`
            : `outbound:${source}:${String(message?.timestamp || '')}:${String(message?.type || '')}:${String(message?.body || '').slice(0, 80)}`;
        if (!shouldProcessEvent(dedupeKey)) {
            return;
        }

        const peer = await resolvePeerFromMessage(message);
        if (!peer.peerId || !peer.peerNumber) {
            return;
        }

        const mediaPayload = await extractMessageMediaPayload(message, {
            fallbackPrefix: 'outbound',
            warnLabel: 'outbound(fromMe)',
            logContext: {
                to: peer.peerId,
                source
            }
        });
        const mediaBase64 = mediaPayload.mediaBase64;
        const mediaMime = mediaPayload.mediaMime;
        const mediaFilename = mediaPayload.mediaFilename;
        const mediaSizeBytes = mediaPayload.mediaSizeBytes;

        const target = peer.peerNumber;
        const outboundPayload = {
            from: target,
            sender: target,
            chat_id: peer.peerId,
            to: target,
            recipient: target,
            number: target,
            message: String(message.body || '').trim(),
            text: String(message.body || '').trim(),
            type: String(message.type || 'chat'),
            message_id: messageId || null,
            timestamp: message.timestamp ? new Date(message.timestamp * 1000).toISOString() : new Date().toISOString(),
            from_me: true,
            has_media: !!message.hasMedia,
            media_mime_type: mediaMime,
            media_filename: mediaFilename,
            media_base64: mediaBase64,
            media_size_bytes: mediaSizeBytes,
            provider: runtimeProvider
        };

        const pushResult = await forwardInboundToErp(outboundPayload);
        if (pushResult.skipped) {
            writeLog('WARN', 'Outbound(fromMe) detectado, mas nao encaminhado ao ERP.', {
                reason: pushResult.reason,
                to: outboundPayload.to,
                source
            });
            return;
        }

        if (!pushResult.ok) {
            writeLog('ERROR', 'Falha ao encaminhar outbound(fromMe) para webhook ERP.', {
                to: outboundPayload.to,
                status: pushResult.status || 0,
                detail: pushResult.error || pushResult.body || null,
                source
            });
            return;
        }

        writeLog('INFO', 'Outbound(fromMe) encaminhado ao ERP com sucesso.', {
            to: outboundPayload.to,
            message_id: outboundPayload.message_id,
            source
        });
    }

    instance.on('qr', async (qr) => {
        writeLog('INFO', 'QR gerado; aguardando autenticacao.');
        updateState({
            status: 'awaiting_qr',
            ready: false,
            hasQr: true,
            lastErrorMessage: null
        });
        lastQrAt = new Date().toISOString();

        qrcodeTerminal.generate(qr, { small: true });
        try {
            lastQrDataUrl = await QRCode.toDataURL(qr);
        } catch (error) {
            lastQrDataUrl = null;
            writeLog('ERROR', 'Falha ao converter QR para DataURL.', { detail: error.message });
            updateState({
                status: 'error',
                hasQr: false,
                lastErrorAt: new Date().toISOString(),
                lastErrorMessage: `Falha ao gerar QR em DataURL: ${error.message}`
            });
        }
    });

    instance.on('authenticated', () => {
        writeLog('INFO', 'Sessao autenticada no WhatsApp.');
        updateState({
            status: 'authenticated',
            ready: false,
            hasQr: false,
            lastErrorMessage: null
        });
        lastQrDataUrl = null;
    });

    instance.on('ready', () => {
        const info = instance.info || {};
        writeLog('INFO', 'Gateway conectado e pronto para envio.', {
            number: info?.wid?.user || null,
            pushname: info?.pushname || null
        });
        updateState({
            status: 'connected',
            ready: true,
            hasQr: false,
            lastReadyAt: new Date().toISOString(),
            lastErrorMessage: null,
            account: {
                number: info?.wid?.user || null,
                pushname: info?.pushname || null,
                platform: info?.platform || null
            }
        });
        lastQrDataUrl = null;
    });

    instance.on('auth_failure', (message) => {
        writeLog('ERROR', 'Falha de autenticacao no WhatsApp.', { detail: String(message || '') });
        updateState({
            status: 'auth_failure',
            ready: false,
            hasQr: false,
            lastErrorAt: new Date().toISOString(),
            lastErrorMessage: String(message || 'Falha de autenticacao no WhatsApp.')
        });
    });

    instance.on('disconnected', (reason) => {
        writeLog('WARN', 'Cliente desconectado.', { reason: reason || null });
        updateState({
            status: 'disconnected',
            ready: false,
            hasQr: false,
            lastDisconnectAt: new Date().toISOString(),
            lastErrorMessage: reason ? `Cliente desconectado: ${reason}` : 'Cliente desconectado.'
        });

        if (restartTimeout) {
            clearTimeout(restartTimeout);
        }
        restartTimeout = setTimeout(() => {
            initializeClient('auto_reconnect').catch((error) => {
                writeLog('ERROR', 'Erro ao reinicializar automaticamente.', { detail: error.message });
            });
        }, config.reconnectDelayMs);
    });

    instance.on('message', async (message) => {
        try {
            if (!message) {
                return;
            }

            if (message.fromMe) {
                await handleOutboundFromMe(message, 'message');
                return;
            }

            const from = String(message.from || '').trim();
            if (!isDirectContactId(from)) {
                return;
            }
            const fromNumber = normalizeContactNumber(from);
            if (!fromNumber) {
                return;
            }

            const mediaPayload = await extractMessageMediaPayload(message, {
                fallbackPrefix: 'inbound',
                warnLabel: 'inbound',
                logContext: { from }
            });
            const mediaBase64 = mediaPayload.mediaBase64;
            const mediaMime = mediaPayload.mediaMime;
            const mediaFilename = mediaPayload.mediaFilename;
            const mediaSizeBytes = mediaPayload.mediaSizeBytes;

            const inboundPayload = {
                from: fromNumber,
                sender: fromNumber,
                number: fromNumber,
                message: String(message.body || '').trim(),
                text: String(message.body || '').trim(),
                type: String(message.type || 'chat'),
                message_id: message?.id?._serialized || null,
                timestamp: message.timestamp ? new Date(message.timestamp * 1000).toISOString() : new Date().toISOString(),
                from_me: false,
                has_media: !!message.hasMedia,
                media_mime_type: mediaMime,
                media_filename: mediaFilename,
                media_base64: mediaBase64,
                media_size_bytes: mediaSizeBytes,
                provider: runtimeProvider
            };

            const pushResult = await forwardInboundToErp(inboundPayload);
            if (pushResult.skipped) {
                writeLog('WARN', 'Inbound recebido, mas nao encaminhado ao ERP.', {
                    reason: pushResult.reason,
                    from: inboundPayload.from
                });
                return;
            }

            if (!pushResult.ok) {
                writeLog('ERROR', 'Falha ao encaminhar inbound para webhook ERP.', {
                    from: inboundPayload.from,
                    status: pushResult.status || 0,
                    detail: pushResult.error || pushResult.body || null
                });
                return;
            }

            writeLog('INFO', 'Inbound encaminhado ao ERP com sucesso.', {
                from: inboundPayload.from,
                message_id: inboundPayload.message_id
            });
        } catch (error) {
            writeLog('ERROR', 'Falha inesperada no handler inbound.', { detail: error?.message || 'unknown' });
        }
    });

    // Captura mensagens enviadas pela propria conta fora do ERP (ex.: app do WhatsApp no celular)
    // para manter a thread sincronizada na Central de Mensagens.
    instance.on('message_create', async (message) => {
        try {
            await handleOutboundFromMe(message, 'message_create');
        } catch (error) {
            writeLog('ERROR', 'Falha inesperada no handler outbound(fromMe).', { detail: error?.message || 'unknown' });
        }
    });
}

async function buildPayloadFromChatMessage(message, chatId, providerId) {
    if (!message) {
        return null;
    }

    const directChatId = String(chatId || '').trim();
    if (!directChatId) {
        return null;
    }

    const number = String(directChatId).replace(/@.*/g, '').replace(/\D/g, '');
    if (!number) {
        return null;
    }

    const body = String(message?.body || '').trim();
    const type = String(message?.type || 'chat').trim() || 'chat';
    const hasMedia = !!message?.hasMedia;
    if (body === '' && !hasMedia) {
        return null;
    }

    const rawTimestamp = Number(message?.timestamp || 0);
    const timestamp = rawTimestamp > 0
        ? new Date(rawTimestamp * 1000).toISOString()
        : new Date().toISOString();

    const mediaPayload = await extractMessageMediaPayload(message, {
        fallbackPrefix: message?.fromMe ? 'history_outbound' : 'history_inbound',
        warnLabel: 'history',
        logContext: {
            chat_id: directChatId,
            message_id: message?.id?._serialized || null
        }
    });
    const mediaMimeFallback = String(message?.mimetype || message?._data?.mimetype || '').toLowerCase().trim() || null;
    const mediaFilenameFallbackRaw = String(message?.filename || message?._data?.filename || '').trim();
    const mediaFilenameFallback = mediaFilenameFallbackRaw !== '' ? sanitizeFileName(mediaFilenameFallbackRaw) : null;
    const mediaMime = mediaPayload.mediaMime || mediaMimeFallback;
    const mediaFilename = mediaPayload.mediaFilename || mediaFilenameFallback;

    return {
        from: number,
        sender: number,
        number,
        to: number,
        recipient: number,
        chat_id: directChatId,
        message: body,
        text: body,
        type,
        message_id: message?.id?._serialized || null,
        timestamp,
        from_me: !!message?.fromMe,
        has_media: hasMedia,
        media_mime_type: mediaMime,
        media_filename: mediaFilename,
        media_base64: mediaPayload.mediaBase64,
        media_size_bytes: mediaPayload.mediaSizeBytes,
        provider: providerId
    };
}

async function collectChatHistoryPayloads(options = {}) {
    if (!state.ready || !client) {
        throw new Error('Gateway nao esta pronto para sincronizar historico.');
    }

    const limitChats = Math.min(80, Math.max(1, Number.parseInt(options.limitChats || '20', 10)));
    const perChat = Math.min(120, Math.max(5, Number.parseInt(options.perChat || '25', 10)));
    const maxTotal = Math.min(2000, Math.max(20, Number.parseInt(options.maxTotal || '500', 10)));
    const sinceSeconds = Math.max(0, Number.parseInt(options.sinceSeconds || '172800', 10));
    const sinceEpoch = sinceSeconds > 0 ? (Date.now() - (sinceSeconds * 1000)) : 0;
    const providerId = config.nodeEnv === 'production' ? 'api_whats_linux' : 'api_whats_local';

    const chats = await client.getChats();
    const directChats = (chats || [])
        .filter((chat) => {
            const chatId = String(chat?.id?._serialized || chat?.id || '').trim().toLowerCase();
            return chatId.endsWith('@c.us') || chatId.endsWith('@s.whatsapp.net');
        })
        .sort((a, b) => Number(b?.timestamp || 0) - Number(a?.timestamp || 0))
        .slice(0, limitChats);

    const items = [];
    for (const chat of directChats) {
        const chatId = String(chat?.id?._serialized || chat?.id || '').trim();
        if (!chatId) {
            continue;
        }

        try {
            const messages = await chat.fetchMessages({ limit: perChat });
            const ordered = Array.isArray(messages)
                ? messages.sort((a, b) => Number(a?.timestamp || 0) - Number(b?.timestamp || 0))
                : [];

            for (const message of ordered) {
                const rawTs = Number(message?.timestamp || 0);
                const tsMs = rawTs > 0 ? rawTs * 1000 : Date.now();
                if (sinceEpoch > 0 && tsMs < sinceEpoch) {
                    continue;
                }

                const payload = await buildPayloadFromChatMessage(message, chatId, providerId);
                if (!payload) {
                    continue;
                }

                items.push(payload);
                if (items.length >= maxTotal) {
                    break;
                }
            }
        } catch (error) {
            writeLog('WARN', 'Falha ao coletar historico de um chat.', {
                chat_id: chatId,
                detail: error?.message || 'unknown'
            });
        }

        if (items.length >= maxTotal) {
            break;
        }
    }

    return {
        items,
        meta: {
            limit_chats: limitChats,
            per_chat: perChat,
            max_total: maxTotal,
            since_seconds: sinceSeconds,
            collected: items.length
        }
    };
}

async function createClient() {
    const chromePath = resolveChromeExecutablePath();
    const newClient = new Client({
        authStrategy: new LocalAuth({
            clientId: config.clientId,
            dataPath: config.sessionPath
        }),
        puppeteer: {
            headless: true,
            executablePath: chromePath,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
            protocolTimeout: 0
        }
    });
    bindClientEvents(newClient);
    return newClient;
}

async function initializeClient(trigger = 'startup', cleanSession = false) {
    if (isInitializing) {
        writeLog('WARN', 'Inicializacao ignorada porque ja existe processo em andamento.', { trigger });
        if (trigger !== 'manual_restart') return;
        writeLog('WARN', 'Forcando nova inicializacao via restart manual (override).');
    }
    isInitializing = true;
    updateState({
        status: trigger === 'startup' ? 'starting' : 'restarting',
        ready: false,
        hasQr: false
    });

    try {
        if (client) {
            try {
                await client.destroy();
                client = null;
            } catch (error) {
                writeLog('WARN', 'Falha ao destruir instancia anterior.', { detail: error.message });
            }
        }

        // Sempre tentamos limpar processos órfãos para evitar o erro "The browser is already running"
        // que trava o SingletonLock do Chrome/Edge
        await forceKillChromium();
        // Pequeno delay para liberar locks do sistema de arquivos
        await new Promise(r => setTimeout(r, 2000));

        const sessionDir = path.join(config.sessionPath, `session-${config.clientId}`);

        // Limpeza profunda da sessao se solicitado
        if (cleanSession) {
            if (fs.existsSync(sessionDir)) {
                try {
                    // Tenta remover a pasta inteira para zerar
                    fs.rmSync(sessionDir, { recursive: true, force: true });
                    writeLog('INFO', 'Pasta de sessao removida (deep clean).');
                } catch (e) {
                    writeLog('WARN', 'Nao foi possivel remover pasta de sessao totalmente.', { detail: e.message });
                }
            }
        } else if (trigger === 'manual_restart' || trigger === 'logout_reinit') {
            // Apenas trava (lock) basica
            const lockFile = path.join(sessionDir, 'Default', 'SingletonLock');
            if (fs.existsSync(lockFile)) {
                try {
                    fs.unlinkSync(lockFile);
                    writeLog('INFO', 'Arquivo de trava (SingletonLock) removido.');
                } catch (e) {
                    writeLog('WARN', 'Falha ao remover lock.', { detail: e.message });
                }
            }
        }

        const chromePath = resolveChromeExecutablePath();
        client = await createClient();
        writeLog('INFO', 'Inicializando client WhatsApp...', { 
            trigger, 
            cleanSession, 
            sessionPath: config.sessionPath,
            executablePath: chromePath || 'default-puppeteer'
        });

        // Timeout de seguranca para a inicializacao (2 minutos)
        const initPromise = client.initialize();
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error('TIMEOUT: client.initialize() demorou mais de 120s')), 120000);
        });

        await Promise.race([initPromise, timeoutPromise]);
    } catch (error) {
        writeLog('ERROR', 'Falha ao iniciar client WhatsApp.', { trigger, detail: error.message });
        const errMsg = String(error.message || '');
        let detailedMsg = errMsg;
        
        if (errMsg.includes('browser is already running')) {
            detailedMsg = 'O navegador ja esta em uso ou travou. Tente a opcao de "Limpeza Profunda" ou feche processos chrome/node no Gerenciador de Tarefas.';
        }

        updateState({
            status: 'error',
            ready: false,
            hasQr: false,
            lastErrorAt: new Date().toISOString(),
            lastErrorMessage: detailedMsg
        });
        throw error;
    } finally {
        isInitializing = false;
    }
}

app.get('/health', (req, res) => {
    return jsonSuccess(res, 'healthy', 'Gateway local ativo.', {
        node_env: config.nodeEnv,
        uptime_seconds: Math.floor((Date.now() - startedAt) / 1000),
        started_at: new Date(startedAt).toISOString()
    });
});

app.use(requireGatewayAuth);
app.use(['/create-message', '/restart', '/status', '/qr', '/self-check-inbound', '/sync-chat-history'], sensitiveLimiter);

app.get('/status', (req, res) => {
    return jsonSuccess(res, state.status, 'Status do gateway local.', {
        ready: state.ready,
        has_qr: state.hasQr && !!lastQrDataUrl,
        qr: (state.hasQr && lastQrDataUrl) ? lastQrDataUrl : null,
        is_initializing: isInitializing,
        uptime_seconds: Math.floor((Date.now() - startedAt) / 1000),
        session_path: config.sessionPath,
        last_qr_at: lastQrAt,
        last_ready_at: state.lastReadyAt,
        last_disconnect_at: state.lastDisconnectAt,
        last_error_at: state.lastErrorAt,
        last_error_message: state.lastErrorMessage,
        account: state.account
    });
});

app.get('/qr', (req, res) => {
    if (state.ready) {
        return jsonSuccess(res, 'connected', 'Gateway ja conectado. QR nao necessario.', {
            qr: null
        });
    }

    if (!lastQrDataUrl) {
        return jsonError(
            res,
            'qr_unavailable',
            'QR Code ainda nao foi gerado. Aguarde ou reinicie o gateway.',
            { has_qr: false },
            404
        );
    }

    return jsonSuccess(res, 'awaiting_qr', 'QR Code disponivel para leitura.', {
        qr: lastQrDataUrl,
        generated_at: lastQrAt
    });
});

app.post('/restart', async (req, res) => {
    const clean = req.body.clean === true;
    try {
        writeLog('WARN', `Reinicio manual solicitado via API. Clean: ${clean}`);
        await initializeClient('manual_restart', clean);
        return jsonSuccess(res, 'restarting', clean ? 'Reinicializacao com limpeza de sessao solicitada.' : 'Reinicializacao solicitada com sucesso.');
    } catch (error) {
        return jsonError(
            res,
            'restart_failed',
            'Falha ao reiniciar o gateway local.',
            { detail: error.message },
            500
        );
    }
});

app.post('/logout', async (req, res) => {
    try {
        writeLog('WARN', 'Solicitacao de logout/desconexao recebida.');
        if (client && state.ready) {
            await client.logout();
            writeLog('INFO', 'Logout concluido com sucesso no WhatsApp.');
        } else if (client) {
            await client.destroy();
            writeLog('INFO', 'Cliente destruido sem logout (nao estava pronto).');
        }
        
        // Reinicializa para gerar novo QR
        setTimeout(() => {
            initializeClient('logout_reinit').catch(e => writeLog('ERROR', 'Erro ao re-iniciar apos logout', { detail: e.message }));
        }, 2000);

        return jsonSuccess(res, 'logging_out', 'Desconexao solicitada. O gateway sera reiniciado para novo QR Code.');
    } catch (error) {
        writeLog('ERROR', 'Falha ao deslogar gateway.', { detail: error.message });
        return jsonError(
            res,
            'logout_failed',
            'Falha ao deslogar o gateway local.',
            { detail: error.message },
            500
        );
    }
});

app.post('/self-check-inbound', async (req, res) => {
    const probeId = `selfcheck_${Date.now()}`;
    const payload = {
        self_check: true,
        source: 'gateway_self_check',
        probe_id: probeId,
        from: '559999999999',
        sender: '559999999999',
        number: '559999999999',
        message: `SELF_CHECK_INBOUND_${probeId}`,
        text: `SELF_CHECK_INBOUND_${probeId}`,
        type: 'system',
        message_id: `selfcheck.${probeId}`,
        timestamp: new Date().toISOString(),
        from_me: false,
        has_media: false,
        provider: config.nodeEnv === 'production' ? 'api_whats_linux' : 'api_whats_local'
    };

    const pushResult = await forwardInboundToErp(payload);
    if (pushResult.skipped) {
        return jsonError(
            res,
            'self_check_skipped',
            'Self-check inbound nao executado pelo gateway.',
            {
                probe_id: probeId,
                reason: pushResult.reason || 'UNKNOWN',
                webhook_url: config.erpWebhookUrl || null
            },
            422
        );
    }

    if (!pushResult.ok) {
        return jsonError(
            res,
            'self_check_failed',
            'Gateway nao conseguiu validar o inbound no ERP.',
            {
                probe_id: probeId,
                webhook_url: config.erpWebhookUrl || null,
                status: pushResult.status || 0,
                detail: pushResult.error || pushResult.body || null,
                target_url: pushResult.target_url || null,
                attempts: pushResult.attempts || []
            },
            502
        );
    }

    return jsonSuccess(res, 'self_check_ok', 'Self-check inbound validado pelo gateway.', {
        probe_id: probeId,
        webhook_url: config.erpWebhookUrl || null,
        webhook_status: pushResult.status || 200,
        erp_response: pushResult.body || null,
        target_url: pushResult.target_url || null,
        attempts: pushResult.attempts || []
    });
});

app.get('/sync-chat-history', async (req, res) => {
    if (!state.ready || !client) {
        return jsonError(
            res,
            'not_ready',
            'WhatsApp ainda nao conectado para sincronizacao do historico.',
            { detail: 'Aguardando autenticacao/QR.' },
            503
        );
    }

    try {
        const limitChats = Number.parseInt(String(req.query.limit_chats || '20'), 10);
        const perChat = Number.parseInt(String(req.query.per_chat || '25'), 10);
        const maxTotal = Number.parseInt(String(req.query.max_total || '500'), 10);
        const sinceSeconds = Number.parseInt(String(req.query.since_seconds || '172800'), 10);

        const result = await collectChatHistoryPayloads({
            limitChats,
            perChat,
            maxTotal,
            sinceSeconds
        });

        return jsonSuccess(res, 'history_synced', 'Historico coletado com sucesso.', {
            items: result.items,
            meta: result.meta
        });
    } catch (error) {
        writeLog('ERROR', 'Falha ao sincronizar historico de chats.', {
            detail: error?.message || 'unknown'
        });
        return jsonError(
            res,
            'history_sync_failed',
            'Falha ao coletar historico de chats no gateway.',
            { detail: error?.message || 'unknown' },
            500
        );
    }
});

app.post('/create-message', async (req, res) => {
    if (!state.ready || !client) {
        return jsonError(
            res,
            'not_ready',
            'WhatsApp ainda nao conectado para envio.',
            { detail: 'Aguardando autenticacao/QR.' },
            503
        );
    }

    const to = String(req.body.to || req.body.number || '').trim();
    const text = String(req.body.message || '').trim();
    const file = req.body.file || req.body.media;
    const format = String(req.body.format || '').trim();
    const descricao = String(req.body.descricao || '').trim();
    const filename = String(req.body.filename || '').trim();
    const mimeFromReq = String(req.body.mime || '').trim();

    if (!to) {
        return jsonError(res, 'validation_error', 'Campo "to" e obrigatorio.', { field: 'to' }, 422);
    }

    if (!text && !file) {
        return jsonError(
            res,
            'validation_error',
            'Informe "message" ou "file" para envio.',
            { field: 'message|file' },
            422
        );
    }

    const chatId = toChatId(to);
    try {
        let sentMessage = null;
        writeLog('INFO', 'Tentativa de envio recebida.', {
            to: chatId,
            type: file ? 'file' : 'text',
            hasText: text.length > 0
        });

        if (file) {
            const extension = format.toLowerCase().replace('.', '');
            if (extension && !config.allowedMime.includes(extension)) {
                return jsonError(
                    res,
                    'validation_error',
                    'Formato de arquivo nao permitido.',
                    { allowed: config.allowedMime },
                    422
                );
            }

            const body = base64Body(file);
            if (!body) {
                return jsonError(
                    res,
                    'validation_error',
                    'Arquivo Base64 invalido.',
                    { field: 'file' },
                    422
                );
            }

            const mime = mimeFromReq || mimeFromFormat(extension || 'png');
            const mediaName = filename || (extension ? `arquivo.${extension}` : 'arquivo.bin');
            const media = new MessageMedia(mime, body, mediaName);
            const caption = descricao || text || undefined;
            const isAudio = mime.startsWith('audio/') || (extension === 'webm' && !req.body.isVideo);
            const sendOptions = {
                caption,
                sendAudioAsVoice: isAudio
            };
            sentMessage = await client.sendMessage(chatId, media, sendOptions);
        } else {
            sentMessage = await client.sendMessage(chatId, text);
        }

        return jsonSuccess(res, 'sent', 'Mensagem enviada com sucesso.', {
            to: chatId,
            message_id: sentMessage?.id?._serialized || null,
            type: file ? 'file' : 'text',
            timestamp: new Date().toISOString()
        });
    } catch (error) {
        writeLog('ERROR', 'Falha ao enviar mensagem.', {
            to: chatId,
            error: error instanceof Error ? {
                message: error.message,
                stack: error.stack,
                name: error.name,
                code: error?.code
            } : error,
            detail: error?.message || String(error)
        });
        updateState({
            lastErrorAt: new Date().toISOString(),
            lastErrorMessage: `Falha no envio: ${error?.message || 'Erro inesperado'}`
        });
        return jsonError(
            res,
            'send_failed',
            'Falha ao enviar mensagem pelo WhatsApp.',
            { detail: error.message },
            500
        );
    }
});

app.use((error, req, res, next) => {
    writeLog('ERROR', 'Erro nao tratado no gateway.', { detail: error?.message || 'unknown' });
    return jsonError(
        res,
        'internal_error',
        'Erro interno no gateway local.',
        { detail: error?.message || 'unknown' },
        500
    );
});

const server = app.listen(config.port, config.host, () => {
    writeLog('INFO', `WhatsApp gateway escutando em http://${config.host}:${config.port}`);
    writeLog('INFO', 'Ambiente inicializado.', { nodeEnv: config.nodeEnv, sessionPath: config.sessionPath, logsDir: config.logsDir });
    if (config.apiToken === '') {
        writeLog('WARN', 'API_TOKEN vazio. Em producao, configure token obrigatoriamente.');
    }
    if (allowedOrigins.length === 0) {
        writeLog('WARN', 'ERP_ORIGIN vazio. Todas as origens serao aceitas.');
    } else {
        writeLog('INFO', 'Origens permitidas carregadas.', { origins: allowedOrigins });
    }
    if (!config.erpWebhookUrl) {
        writeLog('WARN', 'ERP_WEBHOOK_URL vazio. Mensagens inbound nao serao encaminhadas para o ERP.');
    } else {
        writeLog('INFO', 'Webhook ERP configurado para inbound.', {
            url: config.erpWebhookUrl,
            forwardInboundEnabled: config.forwardInboundEnabled
        });
    }
    initializeClient('startup').catch((error) => {
        writeLog('ERROR', 'Falha ao iniciar no boot.', { detail: error.message });
    });
});

async function gracefulShutdown(signal) {
    writeLog('WARN', `Recebido sinal ${signal}. Encerrando gateway.`);
    if (restartTimeout) {
        clearTimeout(restartTimeout);
    }

    try {
        if (client) {
            await client.destroy();
        }
    } catch (error) {
        writeLog('ERROR', 'Falha ao encerrar client.', { detail: error.message });
    }

    server.close(() => {
        process.exit(0);
    });
}

process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
