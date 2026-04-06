# Correcao - Central de Mensagens: timeout no polling incremental

Data: 02/04/2026  
Release associada: `v2.10.10`

## Problema observado

- console com repeticao de erro:
  - `[CentralMensagens] falha no polling incremental`
  - `Tempo limite excedido (20s)`
- requests de polling ficavam bloqueadas em cascata sob sincronizacao inbound pesada.

## Causa raiz

- o polling rapido estava acionando sincronizacao pesada de historico do gateway em endpoints chamados continuamente;
- havia contenção por lock de sessão entre requests AJAX concorrentes do mesmo usuário.

## Ajuste aplicado

- separação de fluxo:
  - polling rapido usa apenas processamento local da fila inbound;
  - sync pesado de histórico permanece em rota dedicada (`sync-inbound`);
- liberação de lock de sessão em endpoints de leitura/sync da Central;
- redução de lote na coleta de histórico do gateway para ciclos mais curtos;
- frontend com backoff progressivo + menor frequência de refresh/lista e log de erro.

## Arquivos alterados

- `app/Controllers/CentralMensagens.php`
- `app/Services/CentralMensagensService.php`
- `public/assets/js/central-mensagens.js`
- `app/Config/SystemRelease.php`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/README.md`

## Resultado esperado

- fim do timeout recorrente no polling incremental;
- melhora de estabilidade da thread mesmo com sincronização inbound ativa.
