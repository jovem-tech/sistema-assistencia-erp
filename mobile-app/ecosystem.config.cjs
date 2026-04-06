module.exports = {
  apps: [
    {
      name: "assistencia-mobile-pwa",
      cwd: "/var/www/sistema-hml/mobile-app",
      script: "npm",
      args: "start -- -H 127.0.0.1 -p 3100",
      env: {
        NODE_ENV: "production",
      },
    },
  ],
};
