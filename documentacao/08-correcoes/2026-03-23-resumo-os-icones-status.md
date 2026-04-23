# Correcao - icones de status no resumo da OS

Data: 23/03/2026

## Problema reportado

No card `Resumo da OS`, os indicadores de preenchimento apareciam como `?` e `??`, em vez de icones visuais de status.

## Correcao aplicada

1. Os placeholders textuais foram substituidos por icones Bootstrap no HTML inicial do resumo lateral.
2. A funcao Javascript `updateResumo()` passou a renderizar:
   - `bi-check-circle-fill` para item preenchido
   - `bi-x-circle-fill` para item pendente
3. O tooltip e o texto acessivel (`visually-hidden`) foram adicionados para manter clareza visual e acessibilidade.

## Arquivos atualizados

- `app/Views/os/form.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/08-correcoes/2026-03-23-resumo-os-icones-status.md`

## Resultado esperado

- O resumo lateral deixa de mostrar interrogacoes como indicador.
- Cada campo passa a exibir icone verde ou vermelho conforme o estado atual do preenchimento.
- O comportamento permanece reativo, sem refresh manual da pagina.
