const erpWebBaseUrl = (process.env.NEXT_PUBLIC_ERP_WEB_BASE_URL || "http://localhost:8084").replace(/\/$/, "");
const appBasePathRaw = (process.env.NEXT_PUBLIC_APP_BASE_PATH || "").trim();
const appBasePath =
  appBasePathRaw === "" ? "" : appBasePathRaw.startsWith("/") ? appBasePathRaw : `/${appBasePathRaw}`;

/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  experimental: {
    typedRoutes: true
  },
  basePath: appBasePath || undefined,
  async rewrites() {
    return [
      {
        source: "/api/v1/:path*",
        destination: `${erpWebBaseUrl}/api/v1/:path*`
      }
    ];
  }
};

export default nextConfig;
