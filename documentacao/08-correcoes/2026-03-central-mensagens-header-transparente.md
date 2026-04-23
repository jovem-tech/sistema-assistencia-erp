# Correção de Layout: Cabeçalho da Central de Mensagens

Data: 19/03/2026
Tipo: UI/UX (Ajuste de Design)

## Alteração Realizada

Remoção do fundo (`background`) e da borda inferior (`border-bottom`) do elemento de cabeçalho da Central de Mensagens (`.cm-page-header`).

## Motivação

O objetivo foi proporcionar um visual mais limpo, moderno e integrado com o restante da interface, removendo a percepção de "barra separada" no topo do módulo de atendimento.

## Arquivos Afetados

- `app/Views/central_mensagens/index.php`: Alteração no bloco de estilos CSS interno para a classe `.cm-page-header`.
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`: Atualização da documentação estrutural do layout.

## Detalhes Técnicos

```css
/* Antes */
.cm-page-header {
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color-translucent);
    ...
}

/* Depois */
.cm-page-header {
    background: transparent;
    border-bottom: 0;
    ...
}
```

Essa mudança permite que o cabeçalho herde o fundo do container pai ou do corpo da página, eliminando a delimitação visual rígida.
