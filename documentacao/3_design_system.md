# Design System e Padronização UX/UI

A evolução da interface foi baseada no conceito de dar um perfil mais *Premium* à aplicação por meio de tecnologias front-end sem perder responsividade.

## Cores Bases Global (CSS Variables Customizadas)
A fundação de cores está engessada no `public/assets/css/estilo.css`. 
O sistema é gerido através de CSS Custom Properties (`:root`), que são mapeadas na construção de estilos e também convertidas em modo claro em overrides nativamente (`[data-theme="light"]`). 

```css
  --primary: #6366f1;
  --secondary: #8b5cf6;
  --success: #10b981;
```

## Glassmorphism Base Framework
Nas áreas e caixas de visualização principais (Dashboard, Painéis de Login, Gestores de Equipamentos), optamos por implementar `backdrop-filter` para alcançar a padronização conhecida como "Glassmorphism", unindo fundos translúcidos no modelo dark/light.
- **Glass Card (`.glass-card`)**: Padrão de caixas em toda a aplicação usando transições e efeito de vidro temperado sobre elementos.

## Renderizações JavaScript Customizadas
Dois fortes elementos complementam o back-end via JS base:
- **jQuery Mask Plugin:** Exibe e confina números e CPFS do lado da interface para diminuir ruídos e poluição no formato de dados enviados aos Controllers CodeIgniter.
- **DataTables:** Uma injeção local de pesquisa e ordenação avançada para painéis HTML (`class=".datatable"`). Usado diretamente nas "Ordens de Serviço", "Clientes" e Tabela do "Painel de Usuários", gerando responsividade automática na contagem de items a partir dos Controllers da aplicação.

## Flexibilidade Multi-Tema (Modo Claro/Escuro)
Para resolver inconsistências visuais em diferentes temas (como textos ocultados ou tabelas disformes quando se troca globalmente de Modo Escuro para Modo Claro), o CSS utiliza total suporte à API nativa do Bootstrap 5 através do atributo `data-bs-theme`.
Dessa forma:
- Fundos de tabela recebem variáveis como `--bs-table-bg: transparent;` e herdam automaticamente o estilo do Glassmorphism, mantendo boa legibilidade tanto à luz do dia quanto no painel noturno.
- Classes engessadas de cores, como `text-light` ou `bg-dark`, foram substituídas globalmente por utilitários autônomos (`text-body`, `text-body-secondary`, `bg-body-tertiary`), que se adaptam ativamente de acordo com a predefinição do usuário.
- Elementos complexos, como relatórios (.dataTable) e grids de listagem, utilizam sombras opacas inteligentes baseadas em matriz CSS translúcida, anulando a possibilidade de fundos brancos brilhantes no modo noturno.

## Modal Lightbox Dinâmico (Fotos & Galeria)
A Galeria de Equipamentos foi otimizada para melhor a retenção do usuário na aplicação.
O atributo visual básico de "abrir em uma nova aba para mostrar detalhes da foto", muito utilizado para ver detalhes ampliados de dispositivos de clientes, foi trocado por um Modal de tela cheia com *Lightbox UX*, que escurece o cenário base e destaca o produto sob demanda.
Ao fechar o modal, os dados são limpos via Vanilla Script para economizar alocação de memória RAM temporal para os clientes de navegação mobile.
