# Modulo: Checklists

## Objetivo

O modulo de `Checklists` estrutura a leitura operacional da entrada, manutencao, controle de qualidade e saida dos equipamentos na OS.

## Controle de acesso

O modulo usa a permissao do fluxo de `OS`:

- `os:visualizar` para consultar modelos e leituras;
- `os:editar` para salvar modelos, itens e respostas da entrada.

## Rotas oficiais

- `GET /checklists/entrada`
- `POST /checklists/entrada/salvar`
- `POST /checklists/entrada/item/salvar`
- `POST /checklists/entrada/item/remover/{id}`
- `GET /checklists/manutencao`
- `GET /checklists/controle-qualidade`
- `GET /checklists/saida`

## Estrutura tecnica

### Camada principal

- controller: `app/Controllers/Checklists.php`
- views: `app/Views/checklists/entrada.php` e `app/Views/checklists/placeholder.php`

### Tabelas relacionadas

- `checklist_tipos`
- `checklist_modelos`
- `checklist_itens`
- `checklist_execucoes`
- `checklist_respostas`
- `checklist_fotos`

## Operacao atual

A tela `Checklist de Entrada` esta funcional e permite:

- cadastrar ou ajustar o modelo por tipo de equipamento;
- manter os itens do checklist em ordem;
- marcar modelos e itens como ativos/inativos;
- reaproveitar o mesmo modelo na abertura da OS.

As telas `Checklist de Manutencao`, `Checklist Controle da Qualidade` e `Checklist de Saida` estao reservadas no menu e usam placeholder tecnico enquanto a evolucao funcional nao e expandida.

## Relacao com a OS

- o checklist de entrada e usado como apoio direto na abertura da OS;
- as pendencias podem ser projetadas no PDF de abertura e nos dados estruturados da OS;
- o fluxo segue integrado ao modulo de Ordens de Servico, sem criar uma trilha paralela.
