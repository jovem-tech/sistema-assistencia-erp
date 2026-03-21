# 2026-03 - Refatoracao da aba Relato do Cliente na abertura da OS

## Problema
A abertura da OS (/os/nova) tinha um painel lateral para editar/reordenar relatos selecionados, o que deixou o fluxo mais pesado no balcão e misturou recepcao com manutencao de base de conhecimento.

## Correcoes aplicadas

- Removida da /os/nova toda a estrutura de painel lateral de relatos selecionados.
- Mantido apenas o fluxo rapido: selecionar item no dropdown e inserir no textarea Relato do Cliente.
- Criado modulo administrativo Defeitos Relatados (/defeitosrelatados) para cadastro e manutencao dos relatos.
- Criada a tabela defeitos_relatados para armazenar categorias e frases rapidas.
- Sidebar reorganizada em **Gestao de Conhecimento** com:
  - Base de Defeitos
  - Defeitos Relatados
- Integracao da /os/nova com defeitos_relatados para carregar os dropdowns por categoria.
- Defeitos comuns tecnicos permanecem apenas em /os/editar/{id}.

## Impacto

- Fluxo de abertura mais simples e rapido.
- Melhor separacao entre relato do cliente e diagnostico tecnico.
- Base de relatos reaproveitavel e administravel em modulo proprio.

## Ajuste complementar (filtro e nomenclatura) 

- Adicionado filtro por categoria na listagem de /defeitosrelatados.
- Corrigidos nomes exibidos de categorias legadas para Áudio e Câmera.
