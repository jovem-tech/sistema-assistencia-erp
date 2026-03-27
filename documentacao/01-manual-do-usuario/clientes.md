# Manual do Usuario - Clientes

Atualizado em 20/03/2026.

## Visao geral
O modulo de Clientes centraliza o cadastro formal de pessoas fisicas e juridicas atendidas pela assistencia tecnica.

Regra operacional atual:
- contatos vindos do WhatsApp entram primeiro em `Pessoas -> Contatos`
- o registro vira cliente quando existir vinculo operacional (principalmente abertura de OS)

## Listagem de clientes
Caminho: `COMERCIAL -> Pessoas -> Clientes`

A tela mostra:
- nome/razao social
- CPF/CNPJ
- telefone
- cidade/UF
- acoes (visualizar, editar, excluir)

Use a busca para filtrar por nome, documento, telefone ou e-mail.

## Cadastrar novo cliente
Caminho: `Clientes -> + Novo Cliente`

Campos obrigatorios:
- `nome_razao`
- `telefone1`

Campos opcionais:
- tipo pessoa (fisica/juridica)
- CPF/CNPJ
- RG/IE
- telefone2
- email
- contato alternativo
- endereco completo (com apoio de CEP)

Comportamento do CEP:
- ao completar um CEP valido, o sistema consulta automaticamente o endereco
- os campos `Endereco`, `Bairro`, `Cidade` e `UF` sao preenchidos sem refresh
- depois do preenchimento automatico, o foco vai para o campo `Numero`

## Visualizar cliente
Na acao `Visualizar`, o ERP exibe:
- dados cadastrais
- historico de OS
- equipamentos vinculados
- bloco CRM (eventos, interacoes, follow-ups)

## Cadastro rapido durante abertura de OS
Na tela de nova OS existe botao `Novo` ao lado do campo cliente.
Ao salvar o modal rapido, o cliente ja volta selecionado no formulario.

Padrao de digitacao aplicado:
- o campo `Nome / Razao Social` normaliza automaticamente o valor para title case enquanto o usuario digita
- caps lock, shift ou digitacao toda em caixa alta/baixa nao alteram o padrao final
- exemplo: `paULO silVA sousa` vira `Paulo Silva Sousa`
- o campo `CEP` do modal rapido usa a mesma consulta automatica de endereco do cadastro principal

## Importacao em lote (CSV)
Caminho: `Clientes -> Importar CSV`

Fluxo:
1. baixar modelo
2. preencher dados
3. enviar arquivo
4. validar e importar

Linhas sem nome ou telefone principal sao descartadas.

## Exclusao
Evite excluir clientes com historico operacional.
Quando necessario, prefira manter cadastro e registrar observacoes.
