const { spawn } = require("node:child_process");
const fs = require("node:fs");
const path = require("node:path");

const rootDir = process.cwd();
const nextDir = path.join(rootDir, ".next");
const manifestContent = JSON.stringify(
  {
    version: 3,
    middleware: {},
    functions: {},
    sortedMiddleware: []
  },
  null,
  2
);

function cleanNextCacheOnDevStart() {
  if (process.env.KEEP_NEXT_CACHE === "1") {
    return;
  }

  try {
    fs.rmSync(nextDir, { recursive: true, force: true });
    console.log("[dev-server] cache .next limpo no inicio para evitar chunks orfaos.");
  } catch (error) {
    console.error("[dev-server] falha ao limpar .next no inicio", error);
  }
}

function ensureDevMiddlewareManifest() {
  const serverDir = path.join(rootDir, ".next", "server");
  if (!fs.existsSync(serverDir)) {
    return;
  }

  const manifestPath = path.join(serverDir, "middleware-manifest.json");
  if (!fs.existsSync(manifestPath)) {
    fs.writeFileSync(manifestPath, `${manifestContent}\n`, "utf8");
  }
}

cleanNextCacheOnDevStart();
ensureDevMiddlewareManifest();
const timer = setInterval(ensureDevMiddlewareManifest, 500);

const nextBin = path.join(rootDir, "node_modules", "next", "dist", "bin", "next");
const args = ["dev", ...process.argv.slice(2)];
const child = spawn(process.execPath, [nextBin, ...args], {
  cwd: rootDir,
  stdio: "inherit",
  env: process.env
});

function shutdown(signal) {
  if (!child.killed) {
    child.kill(signal);
  }
}

process.on("SIGINT", () => shutdown("SIGINT"));
process.on("SIGTERM", () => shutdown("SIGTERM"));
process.on("exit", () => clearInterval(timer));

child.on("exit", (code, signal) => {
  clearInterval(timer);
  if (signal) {
    process.kill(process.pid, signal);
    return;
  }
  process.exit(code ?? 0);
});
