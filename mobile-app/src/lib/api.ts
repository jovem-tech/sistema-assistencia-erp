import { clearSession, getAccessToken } from "./auth";

const appBasePathRaw = (process.env.NEXT_PUBLIC_APP_BASE_PATH || "").trim();
const appBasePath =
  appBasePathRaw === "" ? "" : appBasePathRaw.startsWith("/") ? appBasePathRaw : `/${appBasePathRaw}`;

const API_BASE = process.env.NEXT_PUBLIC_ERP_API_BASE_URL || `${appBasePath}/api/v1`;

type ApiResponse<T> = {
  status: "success" | "error";
  data: T;
  error: null | {
    code: string;
    message: string;
    details?: unknown;
  };
  meta?: Record<string, unknown>;
};

export async function apiRequest<T>(
  path: string,
  init: RequestInit = {},
  options: { skipAuth?: boolean } = {}
): Promise<T> {
  const isFormData = typeof FormData !== "undefined" && init.body instanceof FormData;
  const headers = new Headers(init.headers || {});
  headers.set("Accept", "application/json");
  if (!isFormData) {
    headers.set("Content-Type", "application/json");
  } else {
    headers.delete("Content-Type");
  }

  if (!options.skipAuth) {
    const token = getAccessToken();
    if (token) {
      headers.set("Authorization", `Bearer ${token}`);
    }
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...init,
    headers
  });

  const payload = (await response.json()) as ApiResponse<T>;

  if (response.status === 401) {
    clearSession();
  }

  if (!response.ok || payload.status === "error") {
    const message = payload?.error?.message || `HTTP ${response.status}`;
    throw new Error(message);
  }

  return payload.data;
}

export function apiBaseUrl(): string {
  return API_BASE;
}
