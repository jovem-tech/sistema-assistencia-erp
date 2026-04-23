# Registro de Correcao: Central de Mensagens - Estabilizacao de Runtime, Sessao e Rede

**Data:** 20/03/2026  
**Escopo:** Frontend Central de Mensagens + View de carregamento + Documentacao

## Objetivo
Reduzir erros recorrentes de runtime (401/503/CORS, resposta HTML inesperada e loops de polling/stream) no modulo `/atendimento-whatsapp`.

## Alteracoes aplicadas

### 1) Runtime unico no frontend
- Arquivo: `app/Views/central_mensagens/index.php`
- Resultado:
  - mantido apenas bootstrap de configuracao (`window.CM_CFG`) + carga do bundle `assets/js/central-mensagens.js`
  - bloco legado inline do chat ficou encapsulado em `<?php if (false): ?> ... <?php endif; ?>` para nao ser enviado/executado

### 2) Hardening de requests e sessao
- Arquivo: `public/assets/js/central-mensagens.js`
- Implementado:
  - `requestJson()` unificado para `getJson()` e `postForm()`
  - tratamento explicito para:
    - `401/403`: alerta de sessao expirada + encerramento de loops + redirecionamento para `/login`
    - `502/503/504`: mensagem operacional de indisponibilidade
    - timeout de requisicao (`AbortError`)
    - falha de rede/CORS (`TypeError`)
    - resposta nao JSON/HTML inesperado

### 3) Encerramento defensivo de ciclo assinc
- Arquivo: `public/assets/js/central-mensagens.js`
- Implementado:
  - `shutdownRuntime()` para fechar polling + SSE
  - bind em `beforeunload` e `pagehide`
  - fechamento de SSE ao ocultar aba (`visibilitychange`) e retomada no retorno
  - throttle de log de erro no polling para evitar flood de console

## Impacto funcional
- Menos loops de erro no console durante indisponibilidade de backend/gateway.
- Menor risco de atualizar estado apos navegao/desmontagem de tela.
- Expiracao de sessao passa a ser tratada de forma clara para o usuario.

## Arquivos alterados nesta correcao
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/08-correcoes/2026-03-20-central-mensagens-estabilizacao-runtime-sessao-rede.md`
