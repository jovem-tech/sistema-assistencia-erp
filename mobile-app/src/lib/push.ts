import { apiRequest } from "./api";

export type PushEnvironment = {
  supported: boolean;
  secureContext: boolean;
  standalone: boolean;
  isIos: boolean;
  iosVersion: string | null;
  iosPushSupportedByVersion: boolean;
  hasServiceWorker: boolean;
  hasNotification: boolean;
  hasPushManager: boolean;
  vapidConfigured: boolean;
  permission: NotificationPermission | "unsupported";
  canRequest: boolean;
  blockingReason: string;
};

export type PushRegisterResult = {
  ok: boolean;
  code:
    | "success"
    | "unsupported"
    | "insecure_context"
    | "ios_requires_standalone"
    | "missing_vapid_key"
    | "permission_denied"
    | "subscription_failed"
    | "subscription_save_failed";
  message: string;
};

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

function isStandaloneDisplayMode(): boolean {
  if (typeof window === "undefined") {
    return false;
  }

  const nav = window.navigator as Navigator & { standalone?: boolean };
  return window.matchMedia("(display-mode: standalone)").matches || nav.standalone === true;
}

function isIosDevice(): boolean {
  if (typeof window === "undefined") {
    return false;
  }
  return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
}

function parseIosVersion(): string | null {
  if (typeof window === "undefined") {
    return null;
  }

  const ua = window.navigator.userAgent;
  const match = ua.match(/OS (\d+)_?(\d+)?_?(\d+)?/i);
  if (!match) {
    return null;
  }

  const major = Number(match[1] || 0);
  const minor = Number(match[2] || 0);
  const patch = Number(match[3] || 0);
  return `${major}.${minor}.${patch}`;
}

function isIosPushVersionSupported(version: string | null): boolean {
  if (!version) {
    return false;
  }

  const parts = version.split(".").map((part) => Number(part || 0));
  const major = parts[0] || 0;
  const minor = parts[1] || 0;
  if (major > 16) {
    return true;
  }
  if (major < 16) {
    return false;
  }
  return minor >= 4;
}

export function getPushEnvironment(): PushEnvironment {
  if (typeof window === "undefined") {
    return {
      supported: false,
      secureContext: false,
      standalone: false,
      isIos: false,
      iosVersion: null,
      iosPushSupportedByVersion: false,
      hasServiceWorker: false,
      hasNotification: false,
      hasPushManager: false,
      vapidConfigured: false,
      permission: "unsupported",
      canRequest: false,
      blockingReason: "Ambiente de navegador indisponivel."
    };
  }

  const secureContext = window.isSecureContext;
  const hasServiceWorker = "serviceWorker" in navigator;
  const hasNotification = "Notification" in window;
  const hasPushManager = "PushManager" in window;
  const supported = hasServiceWorker && hasNotification && hasPushManager;
  const standalone = isStandaloneDisplayMode();
  const isIos = isIosDevice();
  const iosVersion = isIos ? parseIosVersion() : null;
  const iosPushSupportedByVersion = !isIos || isIosPushVersionSupported(iosVersion);
  const vapidConfigured = (process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY || "").trim() !== "";
  const permission = hasNotification ? Notification.permission : "unsupported";

  let blockingReason = "";
  if (!secureContext) {
    blockingReason = "Notificacoes exigem HTTPS valido.";
  } else if (!supported) {
    blockingReason = "Este navegador/dispositivo nao suporta Web Push.";
  } else if (isIos && !iosPushSupportedByVersion) {
    blockingReason = "iOS sem suporte a push web. Necessario iOS 16.4+.";
  } else if (isIos && !standalone) {
    blockingReason = "No iPhone, instale o app na Tela de Inicio para habilitar notificacoes.";
  } else if (!vapidConfigured) {
    blockingReason = "Configuracao de push pendente no servidor (VAPID).";
  }

  const canRequest = blockingReason === "";

  return {
    supported,
    secureContext,
    standalone,
    isIos,
    iosVersion,
    iosPushSupportedByVersion,
    hasServiceWorker,
    hasNotification,
    hasPushManager,
    vapidConfigured,
    permission,
    canRequest,
    blockingReason
  };
}

export async function registerPushSubscription(deviceLabel = "mobile-pwa"): Promise<PushRegisterResult> {
  const env = getPushEnvironment();
  if (!env.supported) {
    return {
      ok: false,
      code: "unsupported",
      message: env.blockingReason || "Web Push indisponivel neste dispositivo."
    };
  }
  if (!env.secureContext) {
    return {
      ok: false,
      code: "insecure_context",
      message: "Notificacoes exigem HTTPS valido."
    };
  }
  if (env.isIos && !env.iosPushSupportedByVersion) {
    return {
      ok: false,
      code: "unsupported",
      message: "Seu iPhone precisa estar no iOS 16.4+ para push web."
    };
  }
  if (env.isIos && !env.standalone) {
    return {
      ok: false,
      code: "ios_requires_standalone",
      message: "No iPhone, instale o app na Tela de Inicio e abra por la."
    };
  }
  if (!env.vapidConfigured) {
    return {
      ok: false,
      code: "missing_vapid_key",
      message: "Chave VAPID nao configurada no app mobile."
    };
  }

  let permission: NotificationPermission = env.permission === "unsupported" ? "default" : env.permission;
  if (permission !== "granted") {
    permission = await Notification.requestPermission();
  }
  if (permission !== "granted") {
    return {
      ok: false,
      code: "permission_denied",
      message: "Permissao de notificacao negada."
    };
  }

  const registration = await navigator.serviceWorker.ready;
  const vapidPublicKey = (process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY || "").trim();

  let subscription: PushSubscription | null = null;
  try {
    subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
      });
    }
  } catch (error) {
    console.error("[PWA] Falha ao criar PushSubscription", error);
    return {
      ok: false,
      code: "subscription_failed",
      message: "Falha ao registrar notificacoes neste dispositivo."
    };
  }

  try {
    const json = subscription.toJSON();
    await apiRequest("/notifications/subscriptions", {
      method: "POST",
      body: JSON.stringify({
        endpoint: json.endpoint,
        keys: json.keys || {},
        device_label: deviceLabel
      })
    });
  } catch (error) {
    console.error("[PWA] Falha ao salvar subscription no ERP", error);
    return {
      ok: false,
      code: "subscription_save_failed",
      message: "Subscription criada, mas falhou ao salvar no ERP."
    };
  }

  return {
    ok: true,
    code: "success",
    message: "Push ativo neste dispositivo."
  };
}

export async function testLocalNotification(): Promise<PushRegisterResult> {
  const env = getPushEnvironment();
  if (!env.supported) {
    return {
      ok: false,
      code: "unsupported",
      message: env.blockingReason || "Web Push indisponivel neste dispositivo."
    };
  }
  if (!env.secureContext) {
    return {
      ok: false,
      code: "insecure_context",
      message: "Notificacoes exigem HTTPS valido."
    };
  }

  let permission: NotificationPermission = env.permission === "unsupported" ? "default" : env.permission;
  if (permission !== "granted") {
    permission = await Notification.requestPermission();
  }
  if (permission !== "granted") {
    return {
      ok: false,
      code: "permission_denied",
      message: "Permissao de notificacao negada."
    };
  }

  try {
    const registration = await navigator.serviceWorker.ready;
    await registration.showNotification("Assistencia Mobile", {
      body: "Teste local de notificacao concluido.",
      icon: "/icons/icon-192.png",
      badge: "/icons/icon-192.png",
      tag: "local-test"
    });
  } catch (error) {
    console.error("[PWA] Falha ao disparar notificacao local", error);
    return {
      ok: false,
      code: "subscription_failed",
      message: "Falha ao disparar notificacao local."
    };
  }

  return {
    ok: true,
    code: "success",
    message: "Notificacao local exibida neste dispositivo."
  };
}
