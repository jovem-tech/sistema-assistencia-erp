# App Mobile/PWA

## Visao geral

Este hub registra a entrada oficial do app mobile/PWA utilizado em conjunto com o ERP.

No ERP web, o acesso passa pela rota protegida `GET /atendimento-mobile`, que:

- exige permissao `atendimento_whatsapp:visualizar`;
- valida se o acesso veio de um dispositivo com perfil mobile, salvo quando `?preview=1` for informado;
- redireciona para o destino configurado em `mobile_pwa_url`;
- usa `/atendimento-mobile-app/login` como fallback quando a configuracao nao estiver preenchida.

## O que este hub cobre

- ponto de entrada do app;
- frontend separado em `mobile-app/` (Next.js);
- regra de redirecionamento a partir do ERP;
- versao e politica de publicacao;
- historico resumido de releases do app.

## Compatibilidade atual

- ERP minimo compativel: `2.11.5`
- linha documentada do app: `0.4.2`

## Links relacionados

- `09-versionamento-e-releases/politica-de-versoes.md`
- `09-versionamento-e-releases/historico-de-versoes.md`
