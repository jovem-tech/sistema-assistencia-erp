export type MobileSession = {
  accessToken: string;
  expiresAt: string;
  user: {
    id: number;
    nome: string;
    email: string;
    perfil: string;
    grupo_id: number;
  };
};

const SESSION_KEY = "erp_mobile_session_v1";

export function getSession(): MobileSession | null {
  if (typeof window === "undefined") {
    return null;
  }

  try {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) {
      return null;
    }
    const parsed = JSON.parse(raw) as MobileSession;
    if (!parsed?.accessToken) {
      return null;
    }
    return parsed;
  } catch (_error) {
    return null;
  }
}

export function setSession(session: MobileSession): void {
  if (typeof window === "undefined") {
    return;
  }
  localStorage.setItem(SESSION_KEY, JSON.stringify(session));
}

export function clearSession(): void {
  if (typeof window === "undefined") {
    return;
  }
  localStorage.removeItem(SESSION_KEY);
}

export function getAccessToken(): string {
  return getSession()?.accessToken || "";
}

