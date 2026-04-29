# Organizacao da visualizacao do orcamento em abas

Data: 29/04/2026
Modulo: Orcamentos

## Problema

A tela `Visualizar Orcamento` concentrava resumo comercial, financeiro, envio, historico e pacotes em uma unica sequencia vertical, o que dificultava leitura e manutencao do contexto pelo operador.

## Correcao aplicada

- a pagina foi reorganizada em abas tematicas;
- os dados do equipamento ganharam uma aba propria com foto e identificacao consolidada;
- o resumo financeiro saiu do topo lateral e foi isolado em `Financeiro do orcamento`;
- historico e rastreabilidade permaneceram juntos na aba `Orcamento`, sem perder suas acoes;
- o controller passou a entregar `equipamentoView` para a view montar a aba de equipamento sem consultas adicionais no frontend.

## Resultado esperado

- leitura mais rapida da proposta;
- menos rolagem longa na tela de visualizacao;
- melhor separacao entre contexto comercial, operacional e financeiro;
- comportamento responsivo preservado em telas compactas.
