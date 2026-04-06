"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { apiRequest } from "@/lib/api";
import { getSession, setSession } from "@/lib/auth";
import { PwaInstallCard } from "@/components/PwaInstallCard";
import { APP_VERSION, APP_VERSION_LABEL } from "@/lib/app-version";

type LoginPayload = {
  access_token: string;
  token_type: string;
  expires_at: string;
  user: {
    id: number;
    nome: string;
    email: string;
    perfil: string;
    grupo_id: number;
  };
};

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (getSession()?.accessToken) {
      router.replace("/conversas");
    }
  }, [router]);

  async function onSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError("");

    try {
      const data = await apiRequest<LoginPayload>(
        "/auth/login",
        {
          method: "POST",
          body: JSON.stringify({
            email,
            password,
            device_name: "mobile-pwa"
          })
        },
        { skipAuth: true }
      );

      setSession({
        accessToken: data.access_token,
        expiresAt: data.expires_at,
        user: data.user
      });

      router.replace("/conversas");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha no login.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <>
      <header className="mobile-header">
        <h1 className="mobile-title">Assistencia Mobile</h1>
        <p className="mobile-subtitle">Acesso exclusivo de tecnicos e atendentes.</p>
      </header>

      <PwaInstallCard />

      <section className="mobile-card">
        <form className="form-block" onSubmit={onSubmit}>
          <input
            type="email"
            placeholder="Email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
          />
          <input
            type="password"
            placeholder="Senha"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />
          <button type="submit" disabled={loading}>
            {loading ? "Entrando..." : "Entrar"}
          </button>
        </form>
        {error ? <p className="error-line">{error}</p> : null}
        <p className="helper-line">
          Base conectada ao mesmo ERP/DB da central web, sem duplicacao de dados.
        </p>
        <p className="helper-line">{APP_VERSION_LABEL}</p>
        <p className="helper-line">
          Canal: {APP_VERSION.channel} | ERP minimo compativel: {APP_VERSION.erpMinVersion} | Referencia:{" "}
          {APP_VERSION.releasedAt}
        </p>
      </section>
    </>
  );
}
