# Modulo: Pacotes de Servicos

## Objetivo

O modulo de `Pacotes de Servicos` organiza ofertas padronizadas com niveis de atendimento, precos de referencia e preview comercial para uso dentro do fluxo de `Orcamentos`.

## Controle de acesso

O modulo reutiliza o RBAC comercial:

- `orcamentos:visualizar` para listar e prÃ©-visualizar pacotes;
- `orcamentos:criar` para cadastrar novos pacotes;
- `orcamentos:editar` para alterar pacotes existentes;
- `orcamentos:excluir` para remover pacotes.

## Rotas oficiais

- `GET /pacotes-servicos`
- `GET /pacotes-servicos/novo`
- `POST /pacotes-servicos/salvar`
- `GET /pacotes-servicos/editar/{id}`
- `POST /pacotes-servicos/atualizar/{id}`
- `GET /pacotes-servicos/preview/{id}`
- `GET /pacotes-servicos/excluir/{id}`

## Estrutura tecnica

### Camada principal

- controller: `app/Controllers/PacotesServicos.php`
- models: `app/Models/PacoteServicoModel.php` e `app/Models/PacoteServicoNivelModel.php`
- views: `app/Views/pacotes_servicos/index.php` e `app/Views/pacotes_servicos/form.php`

### Tabelas relacionadas

- `pacotes_servicos`
- `pacotes_servicos_niveis`
- `orcamento_pacote_links`
- `pacotes_ofertas`

## Operacao atual

O pacote e cadastrado com:

- nome;
- categoria;
- tipo de equipamento;
- servico de referencia;
- descricao;
- ordem de apresentacao;
- status ativo/inativo.

Cada pacote trabalha com tres niveis fixos:

- `basico`
- `completo`
- `premium`

O preview comercial reutiliza a view publica da oferta para validar a aparencia do pacote antes do envio.

## Relacao com Orcamentos

- os pacotes sao usados como referencia comercial no modulo de Orcamentos;
- a tela de Orcamentos pode detectar, gerar e enviar ofertas de pacote;
- o preview e o fluxo publico continuam baseados no mesmo registro do pacote, sem criar duplicidade de cadastro.
