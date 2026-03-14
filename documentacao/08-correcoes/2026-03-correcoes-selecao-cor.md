# Correção e Estabilização: Seletor de Cores e Formulários Ajax

Data: 2026-03-14
Responsável: Antigravity

## 1. Problema: ReferenceError: updateColorUI is not defined
**Causa**: As funções de manipulação de cores foram declaradas dentro de um escopo restrito do jQuery `$(document).ready()`. Como o catálogo de cores é gerado dinamicamente e utiliza atributos `onclick` (inline), ele não tinha acesso global a essas funções.
**Correção**: As funções `updateColorUI`, `updateColorUIOS`, `buildCatalog` e `buildCatalogOS` foram explicitamente expostas ao objeto `window` (ex: `window.updateColorUI = ...`), garantindo acessibilidade global.

## 2. Problema: Inconsistência de IDs no modal de OS
**Causa**: O formulário de "Novo Equipamento" dentro da OS utilizava o ID `formNovoEquipamento` no HTML, mas o JavaScript tentava submeter via `formNovoEquipAjax`.
**Correção**: Normalizado o ID para `formNovoEquipAjax`, garantindo que o processamento via Ajax ocorra sem recarregar a página e retorne o ID do novo equipamento corretamente para a OS.

## 3. Problema: Conflito de re-declaração de 'defeitosSelecionados'
**Causa**: O uso de `const defeitosSelecionados` em um arquivo que poderia ser carregado múltiplas vezes ou compartilhado no mesmo escopo via include gerava erro de sintaxe.
**Correção**: Alterado de `const` para `var`, permitindo a re-declaração sutil controlada pela lógica condicional do PHP sem quebrar a execução do script.

## 4. Problema: Chamada de função incorreta no Modal OS
**Causa**: O modal de OS tentava chamar `hexToRgb`, mas a função local correta era `hexToRgbOS`.
**Correção**: Corrigida a referência da função, evitando erros de "undefined" durante a detecção inteligente de cor por imagem.

## 5. Problema: TypeError: window.openDocPage is not a function
**Causa**: O botão de "Ajuda" adicionado nos cabeçalhos das páginas solicitava uma função global que só existia dentro do escopo da própria Wiki.
**Correção**: Implementada a função global `window.openDocPage` no arquivo `public/assets/js/scripts.js`. A nova implementação inclui um mapeamento automático que traduz nomes simples (ex: 'equipamentos') para o caminho real dos arquivos de manual dentro da estrutura de pastas da documentação, garantindo que o link funcione em qualquer parte do sistema.

## 6. Problema: Menu "Segurança" não expandia corretamente
**Causa**: Falta de tags de fechamento (`</ul>` e `</div>`) no final do bloco de colapso do Bootstrap no arquivo `sidebar.php`, além de redundância de fechamento no final do arquivo que quebrava o DOM.
**Correção**: Reestruturadas as tags de fechamento do menu `Segurança` e removidos blocos duplicados de `</ul></div>` ao final do sidebar. O menu agora expande e retrai exibindo Usuários e Níveis de Acesso conforme o planejado.

## 7. Problema: Error 500 ao atualizar funcionário (Undefined array key "ativo")
**Causa**: O Controller de Funcionários tentava acessar `$dados['ativo']` diretamente sem verificar se o campo existia. Em formulários HTML, se um checkbox não estiver marcado, ele não é enviado na requisição POST, gerando um erro de índice indefinido no PHP.
**Correção**: Implementada verificação com `isset()` nos métodos `store()` e `update()` do `Funcionarios.php`. Agora o sistema atribui corretamente o valor `0` (inativo) caso o campo não venha na requisição, evitando o erro de execução.
