"use client";

import { useEffect } from "react";

export function PwaBoot() {
  useEffect(() => {
    if (!("serviceWorker" in navigator)) {
      return;
    }

    if (process.env.NODE_ENV !== "production") {
      navigator.serviceWorker
        .getRegistrations()
        .then((registrations) => Promise.all(registrations.map((registration) => registration.unregister())))
        .catch((error) => {
          console.error("[PWA] Falha ao limpar service workers no ambiente local", error);
        });

      if ("caches" in window) {
        caches
          .keys()
          .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
          .catch((error) => {
            console.error("[PWA] Falha ao limpar caches no ambiente local", error);
          });
      }

      return;
    }

    const basePathRaw = (process.env.NEXT_PUBLIC_APP_BASE_PATH || "").trim();
    const normalizedBasePath =
      basePathRaw === "" ? "" : basePathRaw.startsWith("/") ? basePathRaw : `/${basePathRaw}`;

    navigator.serviceWorker.register(`${normalizedBasePath}/sw.js`).catch((error) => {
      console.error("[PWA] Falha ao registrar service worker", error);
    });
  }, []);

  return null;
}
