# 2026-03-25 - Timeout de inatividade da sessao

## Problema

O sistema mantinha dois tempos diferentes para expirar a sessao:

- expiracao tecnica do CodeIgniter em `app/Config/Session.php`
- expiracao por inatividade fixa no `AuthFilter`

Na pratica, isso fazia o usuario perder tempo em formularios longos porque a sessao podia expirar de forma pouco previsivel e o erro so aparecia na tentativa de salvar.

## Correcao aplicada

### Backend
- criada a chave de configuracao `sessao_inatividade_minutos`
- `Configuracoes::save()` passou a sanitizar esse valor entre `5` e `1440` minutos
- `AuthFilter` passou a usar `get_session_inactivity_seconds()` como fonte unica do timeout de inatividade
- para requisicoes AJAX/fetch, o filtro agora responde `401` em JSON com:
  - `auth_required`
  - `session_expired`
  - `message`
  - `redirect_url`
- criado o endpoint protegido `GET /sessao/heartbeat`

### Sessao tecnica do framework
- `app/Config/Session.php` passou a elevar a expiracao tecnica do CodeIgniter acima do timeout configurado de inatividade
- objetivo: evitar que a expiracao interna do framework contradiga o timeout operacional definido pelo sistema

### Frontend
- `layouts/main.php` e `layouts/embed.php` agora publicam meta tags da sessao
- `public/assets/js/scripts.js` ganhou monitor global de sessao:
  - rastreia digitacao, clique, foco, toque, scroll e mousemove
  - envia heartbeat discreto enquanto houver atividade real
  - exibe SweetAlert2 quando a sessao expira
  - intercepta `401` de `fetch` e `jQuery.ajax`
  - redireciona a janela correta para o login, inclusive em embed/iframe

## Impacto funcional

- administradores podem ajustar o timeout de inatividade em `Configuracoes -> Sessao e Seguranca`
- formularios longos deixam de expirar silenciosamente no meio do preenchimento quando o usuario continua ativo
- quando a sessao realmente expira, o motivo aparece imediatamente ao usuario

## Arquivos principais

- `app/Config/Session.php`
- `app/Config/Routes.php`
- `app/Controllers/Configuracoes.php`
- `app/Controllers/Sessao.php`
- `app/Filters/AuthFilter.php`
- `app/Helpers/sistema_helper.php`
- `app/Views/configuracoes/index.php`
- `app/Views/layouts/main.php`
- `app/Views/layouts/embed.php`
- `public/assets/js/scripts.js`
