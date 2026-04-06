# Correcao - Central de Mensagens: composer com altura compacta forcada

Data: 02/04/2026  
Release associada: `v2.10.6`

## Problema observado

Em alguns ciclos de uso da tela `/atendimento-whatsapp`, o `textarea` do composer permanecia visualmente alto mesmo vazio, causando desarmonia com o botao de envio e desconforto na usabilidade.

Elemento afetado:

```html
<textarea class="form-control cm-compose-textarea" id="cmMensagem" rows="1"></textarea>
```

## Ajuste aplicado

- a altura base do campo foi reforcada com prioridade alta no CSS para impedir que o `textarea` permaneca maior que o botao `Enviar` quando estiver vazio;
- o algoritmo de auto-resize passou a limpar o estado vazio de forma agressiva, removendo sobra de altura inline apos restauracao de rascunho ou troca de conversa;
- a tela reaplica o resize do composer apos o bootstrap e apos abrir conversa, reduzindo a chance de o campo ficar preso em altura antiga;
- o breakpoint `<=360px` recebeu o mesmo ajuste compacto, preservando alinhamento visual no mobile.

## Arquivos alterados

- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`
- `app/Config/SystemRelease.php`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/README.md`

## Resultado esperado

- composer vazio com altura inicial harmonizada ao botao de envio;
- crescimento progressivo somente quando a mensagem realmente exigir mais linhas;
- menor risco de o campo permanecer "alto demais" apos troca de conversa, restauracao de rascunho, recarga parcial ou resize da janela.
