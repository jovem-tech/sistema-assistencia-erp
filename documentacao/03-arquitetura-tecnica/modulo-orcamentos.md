# Arquitetura Tecnica - Modulo Orcamentos

## Objetivo

Este documento resume a estrutura tecnica do modulo de `Orcamentos` no ERP web, com foco no fluxo `Dados do Cliente` consolidado na release `2.15.1`.

## Estrutura principal

- controller web: `app/Controllers/Orcamentos.php`
- models principais:
  - `app/Models/OrcamentoModel.php`
  - `app/Models/OrcamentoItemModel.php`
  - `app/Models/OrcamentoEnvioModel.php`
  - `app/Models/OrcamentoAprovacaoModel.php`
  - `app/Models/OrcamentoStatusHistoricoModel.php`
- services principais:
  - `app/Services/OrcamentoService.php`
  - `app/Services/OrcamentoPdfService.php`
  - `app/Services/OrcamentoMailService.php`
  - `app/Services/OrcamentoLifecycleService.php`
  - `app/Services/OrcamentoConversaoService.php`
- views:
  - `app/Views/orcamentos/form.php`
  - `app/Views/orcamentos/index.php`
  - `app/Views/orcamentos/show.php`

## Fluxo tecnico de Dados do Cliente

### 1. Lookup inicial

O frontend usa `clienteLookupInitial` para montar o estado da selecao quando o orcamento ja existe ou chega com prefill.

Esse payload e montado em `Orcamentos::buildClienteLookupInitial()`.

Na release `2.15.1`, o payload inicial passou a carregar tambem:

- `contato_adicional_nome`
- `contato_adicional_telefone`

### 2. Busca dinamica

O Select2 consulta `Orcamentos::lookupClienteContato()`.

O endpoint combina:

- clientes vindos de `searchClientesForLookup()`;
- contatos vindos de `searchContatosForLookup()`.

No lado de clientes, a busca agora inclui:

- `nome_razao`
- `nome_contato`
- `cpf_cnpj`
- `email`
- `telefone1`
- `telefone_contato`

No lado de contatos, o backend pode herdar o resumo do contato adicional do cliente relacionado para manter a UI consistente mesmo quando a selecao principal for um contato.

### 3. Aplicacao no formulario

No frontend, `applyLookupSelection()` atualiza:

- `cliente_id`
- `contato_id`
- `telefone_contato`
- `email_contato`
- estado do campo `cliente_nome_avulso`
- card `Contato adicional do cliente`

O card informativo usa o mesmo payload do lookup, sem dependencia de nova chamada AJAX.

### 4. Persistencia

Antes de salvar, `validateContatoPayload()` e `resolveClienteContatoPayload()` tratam o bloco de cliente e contato.

Regra consolidada em `2.15.1`:

- `telefone_contato` pode ser vazio;
- se vier preenchido, continua passando por `isWhatsAppPhoneValid()`;
- quando salvo vazio, o payload final grava `null`;
- fallbacks de cliente e contato ainda podem reaproveitar telefones ja cadastrados em etapas posteriores do fluxo.

## Comportamento reativo

O bloco `Dados do Cliente` foi mantido dentro do padrao reativo do sistema:

- selecionar cliente atualiza telefone, email e contato adicional imediatamente;
- limpar a selecao remove o resumo adicional sem refresh;
- carregar uma edicao existente reaplica o mesmo estado inicial no primeiro render.

## Help mapping

O botao `Ajuda` do formulario usa `openDocPage('orcamentos')`, que aponta para:

- `documentacao/01-manual-do-usuario/orcamentos.md`

Com isso, a ajuda contextual da tela fica alinhada ao comportamento publicado na release `2.15.1`.
