"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { MobileNav } from "@/components/MobileNav";
import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import { getPushEnvironment, registerPushSubscription, testLocalNotification, type PushEnvironment } from "@/lib/push";

type NotificationItem = {
  id: number;
  tipo_evento: string;
  titulo: string;
  corpo: string;
  rota_destino: string | null;
  lida_em: string | null;
  created_at: string | null;
};

type NotificationResponse = {
  items: NotificationItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
  };
  whatsapp_connection?: WhatsAppConnectionStatus | null;
};

type WhatsAppConnectionStatus = {
  enabled: boolean;
  provider: string;
  provider_label: string;
  ok: boolean;
  status_code: number | null;
  failure_type: string | null;
  message: string;
  checked_at: string | null;
  last_check_status?: string | null;
  last_check_message?: string | null;
  last_check_at?: string | null;
};

export default function NotificationsPage() {
  const router = useRouter();
  const [items, setItems] = useState<NotificationItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [pushStatus, setPushStatus] = useState("");
  const [pushEnv, setPushEnv] = useState<PushEnvironment | null>(null);
  const [whatsStatus, setWhatsStatus] = useState<WhatsAppConnectionStatus | null>(null);

  async function loadData() {
    setLoading(true);
    setError("");
    try {
      const data = await apiRequest<NotificationResponse>("/notifications?per_page=60&page=1");
      setItems(data.items || []);
      setWhatsStatus(data.whatsapp_connection || null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar notificacoes.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (!getSession()?.accessToken) {
      router.replace("/login");
      return;
    }
    setPushEnv(getPushEnvironment());
    loadData();
    const timer = window.setInterval(loadData, 15000);
    return () => window.clearInterval(timer);
  }, [router]);

  async function onEnablePush() {
    setPushStatus("Ativando...");
    try {
      const result = await registerPushSubscription("mobile-pwa");
      setPushStatus(result.message);
    } catch (err) {
      setPushStatus(err instanceof Error ? err.message : "Falha ao ativar push.");
    } finally {
      setPushEnv(getPushEnvironment());
    }
  }

  async function onLocalTest() {
    setPushStatus("Testando notificacao local...");
    try {
      const result = await testLocalNotification();
      setPushStatus(result.message);
    } catch (err) {
      setPushStatus(err instanceof Error ? err.message : "Falha no teste local.");
    } finally {
      setPushEnv(getPushEnvironment());
    }
  }

  async function onRead(notificationId: number) {
    await apiRequest(`/notifications/${notificationId}/read`, {
      method: "PUT"
    });
    await loadData();
  }

  async function onReadAll() {
    await apiRequest("/notifications/read-all", {
      method: "PUT"
    });
    await loadData();
  }

  return (
    <>
      <header className="mobile-header">
        <h1 className="mobile-title">Notificacoes</h1>
        <p className="mobile-subtitle">Push + avisos operacionais da central</p>
      </header>

      <section className="mobile-card">
        <div className="form-block">
          <button type="button" onClick={onEnablePush} disabled={pushEnv ? !pushEnv.canRequest : true}>
            Ativar notificacoes no celular
          </button>
          <button type="button" onClick={onReadAll}>
            Marcar todas como lidas
          </button>
          <button type="button" onClick={onLocalTest}>
            Testar notificacao local
          </button>
        </div>
        {pushEnv ? (
          <>
            <p className="helper-line">
              Permissao atual: <strong>{pushEnv.permission}</strong>
            </p>
            <p className="helper-line">
              Diagnostico: HTTPS {pushEnv.secureContext ? "ok" : "falho"} | SW{" "}
              {pushEnv.hasServiceWorker ? "ok" : "falho"} | PushManager{" "}
              {pushEnv.hasPushManager ? "ok" : "falho"} | Notification{" "}
              {pushEnv.hasNotification ? "ok" : "falho"} | VAPID {pushEnv.vapidConfigured ? "ok" : "falho"}
            </p>
            {pushEnv.isIos ? (
              <p className="helper-line">
                iPhone: iOS {pushEnv.iosVersion || "nao identificado"} | suporte push{" "}
                {pushEnv.iosPushSupportedByVersion ? "ok" : "falho"} | standalone{" "}
                {pushEnv.standalone ? "ok" : "falho"}.
              </p>
            ) : null}
            {pushEnv.isIos ? (
              <p className="helper-line">
                Abra este app pela Tela de Inicio (PWA instalado) para liberar o pedido de notificacao.
              </p>
            ) : null}
            {!pushEnv.canRequest && pushEnv.blockingReason ? (
              <p className="error-line">{pushEnv.blockingReason}</p>
            ) : null}
          </>
        ) : null}
        {pushStatus ? <p className="helper-line">{pushStatus}</p> : null}
        {whatsStatus ? (
          <article className={`connection-status-card ${whatsStatus.ok ? "is-ok" : "is-fail"}`}>
            <div className="connection-status-head">
              <strong>WhatsApp ERP</strong>
              <span className={`connection-status-pill ${whatsStatus.ok ? "is-ok" : "is-fail"}`}>
                {whatsStatus.ok ? "Conectado" : "Instavel"}
              </span>
            </div>
            <p className="helper-line">
              Provedor: <strong>{whatsStatus.provider_label || whatsStatus.provider || "Nao definido"}</strong> |{" "}
              Integracao: {whatsStatus.enabled ? "ativa" : "desativada"}
            </p>
            <p className={`helper-line ${whatsStatus.ok ? "connection-note-ok" : "connection-note-fail"}`}>
              {whatsStatus.message}
            </p>
            {whatsStatus.checked_at ? (
              <p className="helper-line">Ultima checagem: {whatsStatus.checked_at}</p>
            ) : null}
          </article>
        ) : null}
        {loading ? <p className="helper-line">Carregando notificacoes...</p> : null}
        {error ? <p className="error-line">{error}</p> : null}

        <div className="list-stack" style={{ marginTop: 10 }}>
          {items.map((item) => (
            <article key={item.id} className="list-item">
              <h3 className="list-item-title">{item.titulo}</h3>
              <p style={{ margin: 0, fontSize: 14 }}>{item.corpo}</p>
              <p className="list-item-subtitle">
                {item.tipo_evento} | {item.created_at || ""}
              </p>
              {!item.lida_em ? (
                <button type="button" onClick={() => onRead(item.id)}>
                  Marcar como lida
                </button>
              ) : (
                <p className="helper-line">Lida em {item.lida_em}</p>
              )}
            </article>
          ))}
        </div>
      </section>

      <MobileNav />
    </>
  );
}
