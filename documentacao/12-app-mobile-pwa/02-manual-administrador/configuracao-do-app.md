# Configuracao do App

## Parametros principais

- `NEXT_PUBLIC_ERP_WEB_BASE_URL`
- `NEXT_PUBLIC_ERP_API_BASE_URL`
- `NEXT_PUBLIC_APP_BASE_PATH`
- `NEXT_PUBLIC_VAPID_PUBLIC_KEY`

## Configuracoes no ERP

- URL oficial do app mobile;
- permissao de acesso por perfil;
- parametros de push;
- controle de dominio e subdominio.

## Regras administrativas

- manter origem oficial unica do app;
- evitar ambientes mistos sem HTTPS;
- validar compatibilidade entre versao do app e ERP antes de publicar;
- documentar toda mudanca em deploy, push ou autenticacao.

