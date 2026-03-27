# Correcao: bloqueio de duplo submit na Nova OS

Data: 2026-03-24

## Problema
Na abertura da OS, o usuario podia clicar repetidamente em `Abrir OS` enquanto o envio ainda estava em andamento. Como nao havia feedback visual imediato nem trava de submissao, isso abria margem para registros duplicados.

## Ajuste aplicado
- O `formOs` passou a controlar estado de envio por `dataset.submitting`.
- Ao confirmar o salvamento final, o formulario exibe overlay de carregamento padrao sobre a area principal.
- O botao principal troca para spinner com texto de processamento.
- Os botoes do rodape ficam desabilitados durante o envio.
- Novos cliques no submit sao ignorados ate a navegacao concluir.
- Em retorno por cache de navegador (`pageshow`), o estado de loading e restaurado para evitar botao preso.

## Arquivos alterados
- `app/Views/os/form.php`
- `public/assets/css/design-system/layouts/os-form-layout.css`

## Resultado esperado
- O usuario percebe claramente que a OS esta sendo salva.
- O formulario nao aceita clique duplo no envio final.
- O risco de duplicidade por reenvio manual do mesmo formulario cai no fluxo padrao da tela `Nova Ordem de Servico`.
