import type { Metadata, Viewport } from "next";
import { PwaBoot } from "@/components/PwaBoot";
import { APP_VERSION } from "@/lib/app-version";
import "./globals.css";

const appBasePath = (process.env.NEXT_PUBLIC_APP_BASE_PATH || "").trim();
const normalizedBasePath =
  appBasePath === "" ? "" : appBasePath.startsWith("/") ? appBasePath : `/${appBasePath}`;

export const metadata: Metadata = {
  title: "Assistencia Mobile",
  description: "PWA mobile da Central de Atendimento integrada ao ERP.",
  manifest: `${normalizedBasePath}/manifest.webmanifest`,
  appleWebApp: {
    capable: true,
    statusBarStyle: "default",
    title: "Assistencia"
  },
  icons: {
    icon: [
      { url: `${normalizedBasePath}/icons/icon-192.png`, sizes: "192x192", type: "image/png" },
      { url: `${normalizedBasePath}/icons/icon-512.png`, sizes: "512x512", type: "image/png" }
    ],
    apple: [
      { url: `${normalizedBasePath}/icons/icon-192.png`, sizes: "192x192", type: "image/png" }
    ]
  }
};

export const viewport: Viewport = {
  themeColor: "#0f172a"
};

export default function RootLayout({
  children
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR">
      <body data-app-version={APP_VERSION.version} data-erp-min-version={APP_VERSION.erpMinVersion}>
        <PwaBoot />
        <main className="app-shell">{children}</main>
      </body>
    </html>
  );
}
