"use client";

import { useEffect, useMemo, useState } from "react";

type InstallOutcome = "accepted" | "dismissed";

type InstallChoice = {
  outcome: InstallOutcome;
  platform: string;
};

interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>;
  userChoice: Promise<InstallChoice>;
}

function isStandaloneDisplayMode(): boolean {
  if (typeof window === "undefined") {
    return false;
  }

  const nav = window.navigator as Navigator & { standalone?: boolean };
  return window.matchMedia("(display-mode: standalone)").matches || nav.standalone === true;
}

export function PwaInstallCard() {
  const [deferredPrompt, setDeferredPrompt] = useState<BeforeInstallPromptEvent | null>(null);
  const [isInstalled, setIsInstalled] = useState(false);
  const [installing, setInstalling] = useState(false);
  const [feedback, setFeedback] = useState("");
  const [isSecure, setIsSecure] = useState(true);

  const isIos = useMemo(() => {
    if (typeof window === "undefined") {
      return false;
    }
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
  }, []);

  useEffect(() => {
    setIsInstalled(isStandaloneDisplayMode());
    setIsSecure(window.isSecureContext);

    const handleBeforeInstallPrompt = (event: Event) => {
      const installEvent = event as BeforeInstallPromptEvent;
      installEvent.preventDefault();
      setDeferredPrompt(installEvent);
    };

    const handleAppInstalled = () => {
      setIsInstalled(true);
      setDeferredPrompt(null);
      setFeedback("Aplicativo instalado com sucesso.");
    };

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.addEventListener("appinstalled", handleAppInstalled);

    return () => {
      window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
      window.removeEventListener("appinstalled", handleAppInstalled);
    };
  }, []);

  async function handleInstallClick() {
    if (!deferredPrompt) {
      return;
    }

    setInstalling(true);
    setFeedback("");

    try {
      await deferredPrompt.prompt();
      const choice = await deferredPrompt.userChoice;
      if (choice.outcome === "accepted") {
        setFeedback("Instalacao iniciada. Aguarde concluir na tela inicial.");
      } else {
        setFeedback("Instalacao cancelada. Voce pode tentar novamente.");
      }
      setDeferredPrompt(null);
    } catch (error) {
      console.error("[PWA] Falha ao abrir prompt de instalacao", error);
      setFeedback("Nao foi possivel abrir o instalador agora.");
    } finally {
      setInstalling(false);
    }
  }

  if (isInstalled) {
    return (
      <section className="mobile-card pwa-install-card">
        <h2 className="pwa-install-title">Aplicativo instalado</h2>
        <p className="helper-line">Este dispositivo ja esta usando o app instalado.</p>
      </section>
    );
  }

  return (
    <section className={`mobile-card pwa-install-card${isSecure ? "" : " is-warning"}`} aria-live="polite">
      <h2 className="pwa-install-title">Instalar aplicativo</h2>

      {!isSecure ? (
        <p className="helper-line">
          Instalar como app exige HTTPS valido no dominio publico. Em IP com HTTP, o navegador bloqueia o
          instalador.
        </p>
      ) : null}

      {isSecure && deferredPrompt ? (
        <button className="pwa-install-button" type="button" onClick={handleInstallClick} disabled={installing}>
          {installing ? "Abrindo instalador..." : "Instalar aplicativo"}
        </button>
      ) : null}

      {isSecure && !deferredPrompt ? (
        <p className="helper-line">Se o banner nao aparecer, use o menu do navegador e toque em Instalar app.</p>
      ) : null}

      {isIos ? (
        <p className="helper-line">No iPhone/iPad: Compartilhar -&gt; Adicionar a Tela de Inicio.</p>
      ) : null}

      {feedback ? <p className="helper-line">{feedback}</p> : null}
    </section>
  );
}
