# Modulo: Clientes

## Finalidade

Gerenciar o cadastro de clientes, pessoas fisicas e juridicas, com dados de contato, endereco, contatos alternativos e relacionamento operacional com equipamentos e ordens de servico.

## Tabelas utilizadas

| Tabela | Papel |
|---|---|
| `clientes` | Cadastro principal do cliente |

## Relacionamentos

```text
clientes (1) -> (N) equipamentos
clientes (1) -> (N) os
```

## Controller

Arquivo principal: `app/Controllers/Clientes.php`

| Metodo | Rota | Descricao |
|---|---|---|
| `index()` | `GET /clientes` | Listagem |
| `create()` | `GET /clientes/novo` | Formulario de cadastro |
| `store()` | `POST /clientes/salvar` | Salva novo cliente |
| `edit($id)` | `GET /clientes/editar/{id}` | Formulario de edicao |
| `update($id)` | `POST /clientes/atualizar/{id}` | Atualiza cliente |
| `delete($id)` | `GET /clientes/excluir/{id}` | Exclui cliente |
| `show($id)` | `GET /clientes/visualizar/{id}` | Detalhes do cliente |
| `search()` | `GET /clientes/buscar` | Busca AJAX |
| `getJson($id)` | `GET /clientes/json/{id}` | Payload usado em edicao rapida |
| `getJson($id)` | `GET /clientes/json-edicao/{id}` | Payload de edicao rapida com permissao `clientes:editar` |
| `salvar_ajax()` | `POST /clientes/salvar_ajax` | Cadastro rapido em modal |
| `atualizar_ajax($id)` | `POST /clientes/atualizar_ajax/{id}` | Atualizacao rapida em modal |
| `downloadCsvTemplate()` | `GET /clientes/modelo-csv` | Baixa modelo de importacao |
| `importCsv()` | `POST /clientes/importar` | Importacao CSV |

## Permissoes requeridas

`visualizar`, `criar`, `editar`, `excluir`, `importar`

## Fluxo operacional normal

```text
1. Atendente abre a Nova OS
2. Busca cliente por nome, telefone ou cadastro existente
3. Se nao encontrar, usa o botao + Novo
4. Preenche nome e telefone
5. O sistema salva e devolve o cliente selecionado para a OS
```

## Regras de negocio

- Apenas `nome_razao` e `telefone1` sao obrigatorios.
- `cpf_cnpj` e `email` podem ser nulos.
- Campos vazios seguem a normalizacao padrao do model/hook do modulo.
- Clientes nao usam permissao `encerrar`; o registro e historico.
- O lookup de CEP e executado no frontend global (`public/assets/js/scripts.js`) e preenche `endereco`, `bairro`, `cidade` e `uf` dentro do formulario/modal correto, sem depender de refresh da pagina.
- Ao concluir o lookup do CEP, o foco operacional vai para `numero`.

## Padrao automatico de nome

- O campo `nome_razao` passa por normalizacao automatica em todos os fluxos de criacao e edicao.
- A regra aplicada e title case por palavra.
- Exemplo:
  - entrada: `paULO silVA sousa`
  - saida persistida: `Paulo Silva Sousa`
- O frontend aplica esse padrao enquanto o usuario digita nos formularios e modais marcados com `data-auto-title-case="person-name"`.
- O backend reaplica a mesma normalizacao em `store()`, `update()`, `salvar_ajax()` e `importCsv()` para impedir inconsistencias por envio manual ou integracao futura sem JS.

## Fluxos com reatividade

- No cadastro rapido da OS, o cliente salvo volta selecionado imediatamente no Select2.
- O card de resumo do cliente e a listagem pai em modo embed sao sincronizados sem refresh completo.
- O cadastro rapido e o formulario principal compartilham o mesmo comportamento automatico de CEP.

## Edicao rapida a partir da OS

Nas releases `2.16.22` e `2.16.23`, o formulario de OS passou a expor e estabilizar o botao `Editar` ao lado do seletor de cliente sempre que o perfil possui permissao de editar clientes.

Fluxo tecnico atual:

- o botao permanece visivel no contexto da OS;
- quando nao existe cliente selecionado, ele fica desabilitado;
- quando existe cliente selecionado, o clique abre o mesmo modal rapido de cliente ja usado para cadastro;
- o modal e aberto imediatamente no clique, usando os dados ja conhecidos do Select2 para evitar a impressao de botao inoperante;
- o carregamento completo dos detalhes passa a usar `GET /clientes/json-edicao/{id}`, respeitando o contexto de permissao `clientes:editar`;
- se a consulta detalhada falhar, o modal permanece aberto e exibe aviso no proprio corpo, permitindo salvar os campos ja carregados;
- na release `2.16.24`, o script da OS deixou de interromper esse fluxo por erro anterior de checklist (`discrepancias is not defined`), restaurando a abertura do modal em tempo de execucao;
- o fechamento do modal tambem passou a limpar/restaurar foco antes do `hide`, evitando aviso de `aria-hidden` com descendente focado no Bootstrap;
- a persistencia da alteracao passa por `POST /clientes/atualizar_ajax/{id}`;
- apos salvar, o Select2, o resumo do cliente e o contexto da OS sao atualizados sem refresh manual.
