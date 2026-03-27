# Correcao: Listagem de OS com expansao responsiva de colunas

Data: 26/03/2026

## Problema

Na listagem de ordens de servico (`/os`), a responsividade escondia informacoes importantes sem oferecer um mecanismo visual claro para recuperar as colunas ocultas por largura de tela.

Isso prejudicava leitura em notebook menor, tablet e mobile.

## Ajuste aplicado

Arquivos tecnicos:
- `app/Views/os/index.php`
- `public/assets/js/os-list-filters.js`
- `public/assets/css/design-system/layouts/os-list-layout.css`
- `public/assets/css/design-system/layouts/responsive-layout.css`
- `app/Controllers/Os.php`

## Novo comportamento

- A tabela ganhou uma coluna inicial de controle com botao `+` / `-`.
- O botao aparece apenas quando a responsividade ocultar uma ou mais colunas.
- Ao expandir a linha, o sistema renderiza os campos ocultos em uma area interna da propria linha.
- Acoes, badges e HTML existente continuam operacionais dentro dessa area expandida.

## Regras de ocultacao progressiva

- ate `1499px`: oculta `Relato`
- ate `1279px`: oculta `Valor Total`
- ate `1099px`: oculta `Acoes`
- ate `919px`: oculta `Status`
- ate `759px`: oculta `Equipamento`
- ate `619px`: oculta `Data Abertura`

## Impacto tecnico

- O frontend passou a controlar visibilidade de colunas e child rows manualmente, sem depender da extensao Responsive do DataTables.
- O backend ajustou o mapeamento de ordenacao da grade para considerar a nova coluna inicial de controle.
- A tabela de OS deixou de entrar no modo generico de stack/card mobile usado por outras grades do sistema.
