# Melhoria de UX: Botão de Logout na Sidebar

## Contexto
O mostrador de perfil no canto inferior esquerdo da sidebar ocupava um espaço considerável e duplicava informações que já estavam presentes no menu superior (navbar). Seguindo a necessidade de otimização de espaço e agilidade operacional, o mostrador foi substituído por um botão direto de logout.

## Implementações Realizadas

### Frontend (Sidebar)
- **Arquivo:** `app/Views/layouts/sidebar.php`
- **Mudança:** Removido o card `user-mini-profile` (Avatar, Nome e Cargo).
- **Novo Elemento:** Adicionado um botão (`.logout-btn`) com estilo `btn-outline-danger` que ocupa toda a largura disponível.
- **Iconografia:** Utilizado o ícone `bi-box-arrow-left` para representar a saída do sistema.

### Estilização (CSS)
- **Arquivo:** `public/assets/css/estilo.css`
- **Responsividade:** Adicionada a classe `.logout-text` à regra de ocultação quando a sidebar está recolhida (`.sidebar.collapsed`).
- **Feedback Visual:** Ao recolher a sidebar, o botão exibe apenas o ícone centralizado, mantendo a consistência visual com os demais itens do menu.

## Benefícios
1. **Limpeza Visual:** Redução de elementos redundantes na interface.
2. **Acesso Rápido:** O botão de saída agora é global e fixo na base da navegação lateral, não exigindo abertura de submenus.
3. **Consistência de Navegação:** Segue o padrão de dashboards modernos onde o logout é um item de destaque ou fixo na base.

## Verificação Técnica
- [x] Ocultação correta do texto no estado `collapsed`.
- [x] Link de logout apontando para o endpoint correto (`base_url('logout')`).
- [x] Estilo consistente com o Design System (Bootstrap 5 + Custom CSS).
