# 2026-03 - Filtro por categoria em Defeitos Relatados e fallback local de assets

## Problemas corrigidos

1. A listagem de `Defeitos Relatados` não tinha filtro por categoria.
2. Havia nomes exibidos incorretamente/garbled na tabela (ícone, ações, Áudio/Câmera).
3. O sistema dependia de CDN para CSS/JS principais; sem DNS/internet, o frontend quebrava com `$ is not defined`.
4. No cadastro de relato faltava visibilidade das categorias já existentes.
5. A inserção rápida em `/os/nova` repetia prefixo `Cliente relata:` em toda linha.

## Implementação

- Adicionado filtro por categoria em `/defeitosrelatados` via query string `?categoria=`.
- Backend atualizado para carregar categorias distintas e filtrar registros no model.
- Labels da interface ajustados para exibição correta de nomes de categoria e cabeçalhos.
- Formulário de novo/edição de relato passou a mostrar categorias já cadastradas para evitar duplicidade.
- Inserção rápida de relatos na OS passou a adicionar apenas o texto do relato (sem prefixo redundante).
- Dependências frontend principais internalizadas em `public/assets/vendor`:
  - Bootstrap
  - Bootstrap Icons (com fontes locais)
  - jQuery
  - DataTables
  - Select2 + tema Bootstrap 5
  - Chart.js
  - jQuery Mask
- `layouts/main.php` alterado para carregar somente arquivos locais no painel autenticado.

## Resultado

- `/defeitosrelatados` agora filtra por categoria.
- Nomes exibidos na interface ficaram consistentes.
- O painel autenticado funciona sem depender de CDN externa.
