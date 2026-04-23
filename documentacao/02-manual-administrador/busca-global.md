# Manual do Administrador: Busca Global

## Escopo atual

A busca global varre:

- modulos e acoes do sistema
- ordens de servico
- ordens de servico legadas por `numero_os_legado`
- clientes
- equipamentos
- mensagens WhatsApp
- servicos
- pecas

Regra operacional de catalogo:
- resultados de `servicos` na busca global devem considerar apenas registros disponiveis para operacao (`status = ativo` e `encerrado_em IS NULL`);
- resultados de `pecas` na busca global devem considerar apenas registros ativos (`ativo = 1`).

## Filtro de OS legado

- O menu de contexto da busca possui a opcao `OS Legado (numero antigo)`.
- Esse filtro e o caminho recomendado quando a equipe estiver trabalhando com numeracao herdada do ERP antigo.
- O backend continua aceitando numero legado tambem na busca geral, mas o filtro dedicado melhora a precisao operacional.

## Padrao de compatibilidade visual

- As labels do menu da navbar foram convertidas para representacao segura, evitando caracteres corrompidos em ambientes com encoding inconsistente.
- Esse cuidado deve ser mantido em futuras alteracoes da navbar, especialmente em deploy manual para VPS.

## Observacao tecnica

Caso um novo modulo precise entrar na busca global, atualize:

- `app/Libraries/GlobalSearchService.php`
- `app/Views/layouts/navbar.php`
