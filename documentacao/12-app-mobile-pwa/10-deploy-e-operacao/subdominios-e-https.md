# Subdominios e HTTPS

## Modelo recomendado

- ERP web: `sistema.seu-dominio`
- app mobile: `app.seu-dominio`

## Regras obrigatorias

- HTTPS valido em ambos os dominios;
- cookies, auth e rewrites alinhados;
- push notifications apenas em origem segura;
- manifest e service worker acessiveis no mesmo dominio do app.
- quando o app usar subdominio dedicado (ex.: `app.seu-dominio`), publicar o Next na raiz desse dominio com `NEXT_PUBLIC_APP_BASE_PATH=` vazio;
- nesse mesmo modelo, `NEXT_PUBLIC_ERP_WEB_BASE_URL` deve usar o dominio HTTPS real do ERP (ex.: `https://sistema.seu-dominio`) para os rewrites `api/v1`;
- quando o app usar subrota no dominio do ERP, usar `NEXT_PUBLIC_APP_BASE_PATH` correspondente (ex.: `/atendimento-mobile-app`).
- no `sw.js`, evitar pre-cache da raiz (`/`) quando ela responder com redirect e limitar a persistencia em cache a assets estaveis e respostas `200 OK`.

## Diagnostico

Em falhas de instalacao ou push, revisar:

- certificado;
- origem do app;
- manifest;
- service worker;
- rotas `/api/v1`.
