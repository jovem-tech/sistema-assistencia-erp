# Correcao - Breakpoint real da tabela de OS

Data: 27/03/2026

## Problema
A tabela `/os` podia permanecer em um perfil largo demais porque o breakpoint era decidido por `window.innerWidth`, mesmo quando a largura realmente disponivel para o card era bem menor devido a sidebar, paddings e wrappers da pagina.

Sintoma visivel:
- `N OS` e `Cliente` se comprimiam e se sobrepunham;
- a responsividade agressiva planejada nao entrava no momento correto;
- a tabela parecia "quebrada" em notebook com zoom 100%.

## Correcao aplicada
- O calculo do breakpoint passou a usar a largura util real do wrapper da tabela.
- A estrategia de ocultacao ficou mais agressiva em notebook/tablet.
- O texto auxiliar da acao de status foi escondido nos perfis comprimidos.
- O mapeamento de `data-label` foi corrigido para nao considerar colunas escondidas como se ainda estivessem visiveis.

## Impacto
- A tabela prioriza legibilidade antes de tentar manter colunas demais abertas.
- Informacoes secundarias continuam acessiveis pelo expansor da linha.
- O modo card/mobile continua funcional e coerente com a estrutura da listagem.
