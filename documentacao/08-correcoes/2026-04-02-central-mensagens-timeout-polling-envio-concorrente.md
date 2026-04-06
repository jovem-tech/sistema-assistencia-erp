# Correcao - Central de Mensagens: timeout entre polling e envio concorrente

Data: 02/04/2026  
Release associada: `v2.10.11`

## Problema observado

- console da Central com repeticao de erro:
  - `[CentralMensagens] falha no polling incremental`
  - `Tempo limite excedido (20s)`
- operador recebia modal de falha no envio por timeout mesmo com gateway ativo em cenarios de concorrencia.

## Causa raiz

- endpoints de leitura ainda carregavam processamento auxiliar no caminho critico, elevando latencia do polling;
- `POST /atendimento-whatsapp/enviar` disputava lock de sessao com polling e outras chamadas AJAX do mesmo usuario;
- timeout de request/envio no frontend estava curto para cenarios de latencia real em VPS.

## Ajuste aplicado

- endpoints de leitura da Central foram mantidos estritamente no fluxo rapido (sem processamento auxiliar no caminho critico);
- endpoint de envio passou a liberar lock de sessao antes de chamar provider;
- timeout global no frontend foi elevado para `30s`;
- timeout de envio passou para `max(25s, timeout global)`.

## Arquivos alterados

- `app/Controllers/CentralMensagens.php`
- `public/assets/js/central-mensagens.js`
- `app/Config/SystemRelease.php`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/10-deploy/deploy-producao.md`
- `documentacao/README.md`

## Resultado esperado

- reducao significativa de timeout em cascata no polling incremental;
- envio mais robusto durante sincronizacao em paralelo;
- experiencia do operador mais estavel na thread ativa.
