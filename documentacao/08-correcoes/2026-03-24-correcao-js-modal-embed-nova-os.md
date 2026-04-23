# Correcao: erro de JavaScript no modal embed da Nova OS

Data: 2026-03-24
Modulo: Ordens de Servico
Arquivo principal: `app/Views/os/form.php`

## Problema
- O modal `Nova OS` em `embed=1` podia falhar ao carregar o JavaScript com o erro:
  - `Uncaught SyntaxError: Identifier 'rgb' has already been declared`
- O erro interrompia a execucao do script inline da pagina e afetava o carregamento correto do formulario embed.

## Causa raiz
- A funcao `updateColorUIOS()` ficou com um trecho duplicado durante ajustes anteriores.
- Dentro do mesmo escopo passaram a existir novas declaracoes de `const rgb`, `const rgbStr`, `const textColor` e `const preview`, gerando erro de parse no navegador.

## Correcao aplicada
- A funcao `updateColorUIOS()` foi consolidada para manter apenas uma implementacao valida.
- O calculo das cores proximas passou a usar `safeHex`, preservando o comportamento tambem quando nenhuma cor estiver selecionada.
- Nenhuma regra de negocio da OS foi alterada.

## Resultado esperado
- O modal `Nova OS` em modo embed volta a abrir sem erro de sintaxe no console.
- A inicializacao do formulario segue normalmente.
- A UI de cor do modal de equipamento continua funcional sem duplicidade de declaracoes JavaScript.
