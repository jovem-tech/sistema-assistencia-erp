# Correcao - Select2 com truncamento responsivo em campos longos

Data: 23/03/2026

## Problema reportado

Campos Select2 com nomes longos, como cliente e equipamento na abertura da OS, ultrapassavam a largura disponivel ou ficavam desalinhados dentro do componente.

## Correcao aplicada

1. A correcao foi centralizada em `public/assets/css/design-system/components/base/select.css`.
2. O container do Select2 passou a respeitar `width: 100%`, `max-width: 100%` e `min-width: 0`.
3. O texto renderizado em selecoes single-line agora usa:
   - `overflow: hidden`
   - `text-overflow: ellipsis`
   - `white-space: nowrap`
4. A selecao passou a reservar espaco interno para a seta e para o botao de limpar, evitando sobreposicao do texto.
5. A solucao foi aplicada como padrao reutilizavel do design system, beneficiando outros Select2 single-line do ERP.

## Arquivos atualizados

- `public/assets/css/design-system/components/base/select.css`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/design-system.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/08-correcoes/2026-03-23-select2-ellipsis-responsivo.md`

## Resultado esperado

- Nomes longos ficam contidos dentro do campo.
- O texto exibe `...` quando excede a largura disponivel.
- O tooltip nativo continua mostrando o valor completo.
- O layout permanece consistente em desktop, tablet e mobile.
