const CACHE_NAME = "assistencia-mobile-v4";
const CORE_ASSETS = [
  "/login",
  "/manifest.webmanifest",
  "/icons/icon-192.png",
  "/icons/icon-512.png",
  "/icons/icon-maskable-512.png",
];

function getScopePath() {
  const scopeUrl = new URL(self.registration.scope);
  return scopeUrl.pathname.replace(/\/$/, "");
}

function withScope(rawPath) {
  const normalized = typeof rawPath === "string" && rawPath.trim() !== "" ? rawPath.trim() : "/";
  const ensured = normalized.startsWith("/") ? normalized : `/${normalized}`;
  const scopePath = getScopePath();

  if (scopePath === "" || scopePath === "/") {
    return ensured;
  }

  if (ensured === scopePath || ensured.startsWith(`${scopePath}/`)) {
    return ensured;
  }

  return `${scopePath}${ensured}`.replace(/\/{2,}/g, "/");
}

function absoluteUrl(rawPath) {
  return new URL(withScope(rawPath), self.registration.scope).toString();
}

function canCacheResponse(request, response) {
  if (!response || !response.ok) {
    return false;
  }

  const requestUrl = new URL(request.url);
  if (requestUrl.origin !== self.location.origin) {
    return false;
  }

  if (requestUrl.pathname.includes("/api/")) {
    return false;
  }

  return true;
}

function normalizeRoute(rawRoute) {
  const route = typeof rawRoute === "string" && rawRoute.trim() !== "" ? rawRoute.trim() : "/conversas";
  const scopePath = getScopePath();

  if (!route.startsWith("/")) {
    return `${scopePath}/${route}`.replace(/\/{2,}/g, "/");
  }

  if (scopePath !== "" && !route.startsWith(`${scopePath}/`) && route !== scopePath) {
    return `${scopePath}${route}`.replace(/\/{2,}/g, "/");
  }

  return route;
}

self.addEventListener("install", (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    await Promise.all(
      CORE_ASSETS.map(async (asset) => {
        const assetUrl = absoluteUrl(asset);
        try {
          const response = await fetch(assetUrl, { cache: "no-store" });
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          await cache.put(assetUrl, response.clone());
        } catch (error) {
          // Nao bloqueia o install do SW por falha pontual de um asset.
          console.error("[SW] Falha ao adicionar asset ao cache", asset, assetUrl, error);
        }
      })
    );
  })());
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.map((key) => (key !== CACHE_NAME ? caches.delete(key) : Promise.resolve()))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET") {
    return;
  }

  if (!req.url.startsWith("http")) {
    return;
  }

  event.respondWith((async () => {
    try {
      const networkResponse = await fetch(req);
      if (canCacheResponse(req, networkResponse)) {
        const responseClone = networkResponse.clone();
        caches.open(CACHE_NAME)
          .then((cache) => cache.put(req, responseClone))
          .catch((error) => console.error("[SW] Falha ao atualizar cache no fetch", error));
      }
      return networkResponse;
    } catch (networkError) {
      const cachedResponse = await caches.match(req);
      if (cachedResponse) {
        return cachedResponse;
      }

      if (req.mode === "navigate") {
        const cachedLogin = await caches.match(absoluteUrl("/login"));
        if (cachedLogin) {
          return cachedLogin;
        }
      }

      console.error("[SW] Falha de rede sem fallback em cache", req.url, networkError);
      return Response.error();
    }
  })());
});

self.addEventListener("push", (event) => {
  let payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (_error) {
    payload = {};
  }

  const title = payload.title || "Assistencia";
  const options = {
    body: payload.body || "Nova atualizacao no atendimento.",
    icon: withScope("/icons/icon-192.png"),
    badge: withScope("/icons/icon-192.png"),
    data: {
      route: normalizeRoute(payload.route || "/conversas")
    }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const route = normalizeRoute(event.notification?.data?.route || "/conversas");

  event.waitUntil(
    clients.matchAll({ type: "window", includeUncontrolled: true }).then((windowClients) => {
      for (const client of windowClients) {
        if ("focus" in client) {
          client.navigate(route);
          return client.focus();
        }
      }
      return clients.openWindow(route);
    })
  );
});
