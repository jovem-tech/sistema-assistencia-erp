"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { MobileNav } from "@/components/MobileNav";
import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";

type Conversation = {
  id: number;
  telefone: string;
  nome_contato: string | null;
  cliente_nome: string | null;
  status: string;
  prioridade: string;
  nao_lidas: number;
  ultima_mensagem_em: string | null;
};

type ConversationResponse = {
  items: Conversation[];
  count: number;
};

export default function ConversasPage() {
  const router = useRouter();
  const [items, setItems] = useState<Conversation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  async function loadData() {
    setLoading(true);
    setError("");
    try {
      const data = await apiRequest<ConversationResponse>("/conversations?limit=80");
      setItems(data.items || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar conversas.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (!getSession()?.accessToken) {
      router.replace("/login");
      return;
    }
    loadData();
    const timer = window.setInterval(loadData, 12000);
    return () => window.clearInterval(timer);
  }, [router]);

  return (
    <>
      <header className="mobile-header">
        <h1 className="mobile-title">Conversas</h1>
        <p className="mobile-subtitle">Atendimento em tempo real ({items.length} conversas)</p>
      </header>

      <section className="mobile-card">
        <button onClick={loadData} type="button">
          Atualizar lista
        </button>
        {loading ? <p className="helper-line">Carregando conversas...</p> : null}
        {error ? <p className="error-line">{error}</p> : null}

        <div className="list-stack" style={{ marginTop: 10 }}>
          {items.map((item) => (
            <Link key={item.id} href={`/conversas/${item.id}`} className="list-item">
              <h3 className="list-item-title">{item.nome_contato || item.cliente_nome || item.telefone}</h3>
              <p className="list-item-subtitle">
                {item.telefone} {item.ultima_mensagem_em ? `| ${item.ultima_mensagem_em}` : ""}
              </p>
              <div className="chip-row">
                <span className="chip status">Status: {item.status || "-"}</span>
                <span className="chip priority">Prioridade: {item.prioridade || "normal"}</span>
                {item.nao_lidas > 0 ? <span className="chip status">{item.nao_lidas} nao lidas</span> : null}
              </div>
            </Link>
          ))}
        </div>
      </section>

      <MobileNav />
    </>
  );
}

