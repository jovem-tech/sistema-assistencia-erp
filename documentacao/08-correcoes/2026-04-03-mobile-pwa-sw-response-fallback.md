# Correcao - Service Worker PWA retornando Response invalido

Data: 03/04/2026

## Sintoma

Erro recorrente no console:

- `sw.js:1 Uncaught (in promise) TypeError: Failed to convert value to 'Response'`.

## Diagnostico

O `event.respondWith` no `fetch` do SW podia receber `undefined` quando:

1. a chamada de rede falhava; e
2. `caches.match(req)` nao encontrava entrada correspondente.

## Correcao aplicada

- Reescrito o fluxo de `fetch` do SW com retorno garantido de `Response`.
- Fallbacks adicionados:
  - resposta do cache quando disponivel;
  - `Response` offline (`503`) quando rede e cache indisponiveis.
- Cache versionado para `assistencia-mobile-v3`.

## Arquivo

- `mobile-app/public/sw.js`

## Validacao recomendada

1. Abrir `https://app.jovemtech.eco.br`.
2. Em DevTools, limpar storage e registrar SW novamente.
3. Confirmar que o erro nao reaparece no console.
