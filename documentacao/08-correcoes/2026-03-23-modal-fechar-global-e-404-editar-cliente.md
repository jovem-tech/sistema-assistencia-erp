# Correção - OS: Ajuste no botão Editar Cliente (404) e Estilização Global de Modais

Data: 23/03/2026

## Solicitação

1. O usuário relatou que ao clicar no botão "Editar" de cliente, o sistema disparava um erro 404 para a rota `/clientes/getJson/ID`.
2. O usuário solicitou que em "todos os modais do sistema" o botão de fechar (`X`) fosse posicionado no canto superior, na cor grafite para tema claro e branco gelo para tema escuro.

## Ajuste Aplicado

Arquivos alterados:
- `app/Views/os/form.php` (Javascript)
- `public/assets/css/design-system/components/composite/modal.css`

Mudanças implementadas:
1. **Erro 404 ao Buscar Cliente:** 
   O Javascript de `form.php` estava usando `fetch(BASE_URL + 'clientes/getJson/ID')`, com base no nome do método do controller. No entanto, as rotas amigáveis do CodeIgniter mapeavam esse método para a URL `/clientes/json/ID`. Corrigido o endpoint no `fetch()` para o mapeamento correto contido em `app/Config/Routes.php`.
2. **Botão de Fechar nos Modais (Global):**
   Adicionada regra CSS customizada universal no arquivo `modal.css` do Design System que afeta todo o sistema:
   - Fixado `position: absolute; top: 15px; right: 15px; z-index: 1060;` no seletor `.modal .btn-close` para que independentemente de o botão vir dentro de cabeçalho, corpo do modal ou imagem pura, ele sempre apareça flutuante no canto superior.
   - Usado o seletor contextual `[data-bs-theme="dark"]` e filtros de sombra e inversão de cor `filter` para atingir a coloração especificada: Tema Claro = Grafite / Tema Escuro = Branco Gelo.

## Resultado Esperado

- O formulário de edição do Cliente agora popula normalmente as informações de NOME, CELULAR, etc, sem apontamentos de `Not Found` na rede.
- Todos os Modais do sistema (seja de dashboard, cadastro rápido ou imagens) receberão o posicionamento fixo do "X" com a consistência de cor exigida.
