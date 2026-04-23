module.exports = {
  apps: [
    {
      name: 'whatsapp-local-gateway',
      script: './server.js',
      cwd: __dirname,
      watch: false,
      autorestart: true,
      max_restarts: 20,
      min_uptime: '10s',
      time: true,
      env: {
        NODE_ENV: 'development',
        SESSION_PATH: './.wwebjs_auth',
        LOGS_DIR: './logs'
      },
      env_production: {
        NODE_ENV: 'production',
        SESSION_PATH: './.wwebjs_auth',
        LOGS_DIR: './logs'
      }
    }
  ]
};
