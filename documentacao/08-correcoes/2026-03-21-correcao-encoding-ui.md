# Correção de Encoding na Interface (Mojibake)

**Data:** 21/03/2026  
**Status:** Concluído

## Problema

Foram identificados textos com caracteres corrompidos na interface (exemplos: `ServiÃ§os`, `RelatÃ³rios`, `autenticaÃ§Ã£o`, `Permiss�es`), afetando títulos, labels, mensagens e comentários de organização de rotas.

## Causa

Arquivos com conteúdo em UTF-8 passaram por histórico de codificação inconsistente em pontos específicos, gerando mojibake em strings visíveis ao usuário.

## Ação aplicada

1. Varredura de arquivos de interface (`app/Views`, `public/assets/js`) por padrões clássicos de mojibake.
2. Correção controlada de conteúdo com reinterpretação de trechos corrompidos.
3. Ajustes manuais finais em labels pontuais que ainda continham caractere de substituição (`�`).
4. Normalização dos comentários de seção em rotas para manter legibilidade do código.

## Arquivos atualizados

- `app/Config/Routes.php`
- `app/Views/equipamentos/form.php`
- `app/Views/estoque/movimentacoes.php`
- `app/Views/grupos/form.php`
- `app/Views/grupos/index.php`
- `app/Views/grupos/permissoes.php`
- `app/Views/layouts/sidebar.php`
- `app/Views/os/form.php`
- `app/Views/relatorios/view_estoque.php`
- `app/Views/relatorios/view_financeiro.php`
- `app/Views/relatorios/view_os.php`
- `app/Views/servicos/form.php`
- `app/Views/servicos/index.php`
- `public/assets/js/scripts.js`

## Validação

- Verificação de sintaxe PHP (`php -l`) nos principais arquivos alterados.
- Verificação de sintaxe JS (`node --check`) em `public/assets/js/scripts.js`.
- Nova varredura por padrões de mojibake em `app/` e `public/assets/` sem ocorrências restantes.

## Impacto

- Nenhuma regra de negócio foi alterada.
- Ajuste exclusivamente textual/encoding para estabilizar a exibição de caracteres na UI.
