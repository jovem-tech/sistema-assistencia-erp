"use client";

import Link from "next/link";
import { useEffect, useMemo, useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { MobileNav } from "@/components/MobileNav";
import { apiBaseUrl, apiRequest } from "@/lib/api";
import { getAccessToken, getSession } from "@/lib/auth";

type Message = {
  id: number;
  direcao: string;
  mensagem: string | null;
  tipo_conteudo: string | null;
  created_at: string | null;
  enviada_em: string | null;
  recebida_em: string | null;
};

type ConversationDetail = {
  conversa: {
    id: number;
    telefone: string;
    nome_contato: string | null;
    cliente_nome: string | null;
    status: string;
    prioridade: string;
  };
  mensagens: Message[];
};

type RealtimeDelta = {
  messages?: Message[];
  cursor?: {
    after_message_id?: number;
  };
};

export default function ConversationDetailPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const conversaId = Number(params?.id || 0);
  const [detail, setDetail] = useState<ConversationDetail | null>(null);
  const [text, setText] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const [streamStatus, setStreamStatus] = useState("offline");
  const eventSourceRef = useRef<EventSource | null>(null);
  const lastMessageIdRef = useRef(0);

  const lastMessageId = useMemo(() => {
    if (!detail?.mensagens?.length) {
      return 0;
    }
    return detail.mensagens.reduce((max, item) => Math.max(max, Number(item.id || 0)), 0);
  }, [detail?.mensagens]);

  useEffect(() => {
    lastMessageIdRef.current = lastMessageId;
  }, [lastMessageId]);

  function mergeMessages(base: Message[], incoming: Message[]): Message[] {
    const map = new Map<number, Message>();
    base.forEach((item) => map.set(Number(item.id || 0), item));
    incoming.forEach((item) => map.set(Number(item.id || 0), item));
    return [...map.values()].sort((a, b) => Number(a.id || 0) - Number(b.id || 0));
  }

  async function loadDetail() {
    try {
      const data = await apiRequest<ConversationDetail>(`/conversations/${conversaId}`);
      setDetail(data);
      setError("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar conversa.");
    }
  }

  async function pullNewMessages(afterId: number) {
    if (!afterId) {
      return;
    }
    try {
      const data = await apiRequest<{ items: Message[] }>(
        `/messages?conversa_id=${conversaId}&after_id=${afterId}&limit=120`
      );
      if (Array.isArray(data.items) && data.items.length > 0) {
        setDetail((prev) => {
          if (!prev) {
            return prev;
          }
          return {
            ...prev,
            mensagens: mergeMessages(prev.mensagens || [], data.items)
          };
        });
      }
    } catch (_error) {
      // Fallback polling silencioso
    }
  }

  useEffect(() => {
    if (!getSession()?.accessToken) {
      router.replace("/login");
      return;
    }
    if (!conversaId) {
      router.replace("/conversas");
      return;
    }

    loadDetail();
    const pollTimer = window.setInterval(() => {
      pullNewMessages(lastMessageIdRef.current);
    }, 10000);

    return () => {
      window.clearInterval(pollTimer);
      if (eventSourceRef.current) {
        eventSourceRef.current.close();
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [router, conversaId]);

  useEffect(() => {
    if (!conversaId) {
      return;
    }

    const token = getAccessToken();
    if (!token) {
      return;
    }

    const url = `${apiBaseUrl()}/realtime/stream?conversa_id=${conversaId}&after_message_id=${lastMessageIdRef.current}&access_token=${encodeURIComponent(token)}`;
    const source = new EventSource(url);
    eventSourceRef.current = source;
    setStreamStatus("online");

    source.addEventListener("delta", (event) => {
      try {
        const payload = JSON.parse((event as MessageEvent).data) as RealtimeDelta;
        const incoming = Array.isArray(payload.messages) ? payload.messages : [];
        if (incoming.length > 0) {
          setDetail((prev) => {
            if (!prev) {
              return prev;
            }
            return {
              ...prev,
              mensagens: mergeMessages(prev.mensagens || [], incoming)
            };
          });
        }
      } catch (_error) {
        // ignora payload invalido
      }
    });

    source.onerror = () => {
      setStreamStatus("reconectando");
      source.close();
      window.setTimeout(() => {
        pullNewMessages(lastMessageIdRef.current);
      }, 1500);
    };

    return () => {
      source.close();
      if (eventSourceRef.current === source) {
        eventSourceRef.current = null;
      }
    };
  }, [conversaId]);

  async function onSend(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const body = text.trim();
    if (!body) {
      return;
    }

    setSending(true);
    setError("");
    try {
      await apiRequest("/messages", {
        method: "POST",
        body: JSON.stringify({
          conversa_id: conversaId,
          mensagem: body
        })
      });
      setText("");
      await pullNewMessages(lastMessageIdRef.current);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao enviar mensagem.");
    } finally {
      setSending(false);
    }
  }

  return (
    <>
      <header className="mobile-header">
        <h1 className="mobile-title">
          {detail?.conversa?.nome_contato || detail?.conversa?.cliente_nome || detail?.conversa?.telefone || "Conversa"}
        </h1>
        <p className="mobile-subtitle">
          Stream: {streamStatus} | Status: {detail?.conversa?.status || "-"} | Prioridade:{" "}
          {detail?.conversa?.prioridade || "normal"}
        </p>
      </header>

      <section className="mobile-card">
        <Link href="/conversas" className="helper-line">
          Voltar para lista
        </Link>

        <div className="list-stack" style={{ marginTop: 10, maxHeight: "60dvh", overflowY: "auto" }}>
          {(detail?.mensagens || []).map((msg) => {
            const outbound = (msg.direcao || "").toLowerCase() === "outbound";
            return (
              <article
                key={msg.id}
                className="list-item"
                style={{
                  marginLeft: outbound ? "20%" : "0",
                  background: outbound ? "#dcfce7" : "#ffffff"
                }}
              >
                <h3 className="list-item-title">{outbound ? "Equipe" : "Cliente"}</h3>
                <p style={{ margin: 0, fontSize: 15 }}>{msg.mensagem || `[${msg.tipo_conteudo || "mensagem"}]`}</p>
                <p className="list-item-subtitle">{msg.recebida_em || msg.enviada_em || msg.created_at || ""}</p>
              </article>
            );
          })}
        </div>

        <form className="compose-wrap" onSubmit={onSend}>
          <input
            value={text}
            onChange={(event) => setText(event.target.value)}
            placeholder="Digite uma mensagem"
            autoComplete="off"
          />
          <button type="submit" disabled={sending}>
            {sending ? "..." : "Enviar"}
          </button>
        </form>

        {error ? <p className="error-line">{error}</p> : null}
      </section>

      <MobileNav />
    </>
  );
}
