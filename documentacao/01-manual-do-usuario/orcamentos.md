# Manual do Usuario - Orcamentos

## Onde acessar
- Sidebar: `Comercial > Orcamentos`.
- Atalho pela OS: botao `Gerar orcamento` na visualizacao da OS.
- Atalho pela Central: botoes `Novo orcamento` e `Gerar e enviar orcamento` no contexto da conversa.

## Criar orcamento rapido
1. Clique em `Novo orcamento rapido`.
2. Selecione o cliente cadastrado ou preencha `Nome do cliente eventual`.
3. Informe telefone/email de contato.
4. Adicione os itens (tipo, descricao, quantidade e valor).
5. Ajuste desconto/acrescimo global.
6. Salve o orcamento.

## Campos importantes
- `Status`: controle operacional do orcamento.
- `Origem`: identifica se veio de OS, conversa ou criacao manual.
- `Validade`: dias e data limite da proposta.
- `Vinculos`: OS, equipamento e conversa (quando existir).

## Status usados
- `rascunho`
- `enviado`
- `aguardando_resposta`
- `aprovado`
- `pendente_abertura_os` (aprovado sem OS vinculada)
- `rejeitado`
- `vencido`
- `cancelado`
- `convertido`

## Regras de edicao
- Orcamentos `aprovado` e `convertido` nao permitem edicao de itens.
- Exclusao permitida apenas para `rascunho`, `cancelado` e `rejeitado`.

## Envio direto pelo modulo (fase 2)
Na tela `Orcamentos > Visualizar`, o card `Envio do Orcamento` permite:
- gerar novo PDF do orcamento;
- abrir/baixar o PDF atual;
- enviar por WhatsApp com mensagem personalizada e opcao de anexar PDF;
- enviar por e-mail com assunto, mensagem adicional e opcao de anexar PDF.

### Fluxo WhatsApp
1. Confira telefone e mensagem.
2. Marque/desmarque `Anexar PDF automaticamente`.
3. Clique em `Enviar WhatsApp`.
4. O sistema registra a tentativa em `Rastreabilidade de envios/aprovacoes`.

### Fluxo E-mail
1. Confira e-mail de destino.
2. Ajuste assunto e mensagem adicional (opcional).
3. Marque/desmarque `Anexar PDF automaticamente`.
4. Clique em `Enviar E-mail`.
5. O sistema registra a tentativa em `Rastreabilidade de envios/aprovacoes`.

## Conversao de aprovado (fase 3)
Na tela `Orcamentos > Visualizar`, quando o status estiver aprovado:
- `Abrir OS e converter`: cria OS automaticamente (quando houver cliente + equipamento) e fecha o orcamento como `convertido`.
- `Converter em execucao OS`: usa OS ja vinculada e marca como `convertido`.
- `Converter em venda`: marca conversao comercial manual (`convertido`).

## Regra de aprovacao publica (fase 3)
- Orcamento aprovado com `OS vinculada` -> status `aprovado`.
- Orcamento aprovado `avulso` (sem OS) -> status `pendente_abertura_os`.
- Orcamento `pendente_abertura_os` fica pronto para abertura de OS pela equipe.

## Automacao de vencimento e follow-up (fase 3)
- Orcamentos `enviado/aguardando_resposta` passam automaticamente para `vencido` apos a validade.
- O sistema gera follow-up no CRM para:
  - retorno de orcamentos aguardando resposta;
  - orcamentos vencidos;
  - orcamentos aprovados pendentes de abertura de OS.
- O painel possui acao manual `Executar automacao` para forcar processamento imediato.

## Orcamento rapido dentro da conversa (fase 3)
Na Central de Mensagens (contexto da conversa):
1. Clique em `Gerar e enviar orcamento`.
2. Informe descricao e valor do item rapido.
3. Confirme `Gerar e enviar`.
4. O sistema cria o orcamento, gera link publico e envia por WhatsApp na mesma conversa.

## Trilha de envio
Cada envio registra:
- canal (`pdf`, `whatsapp`, `email`);
- destino (telefone/e-mail/contexto interno);
- status (`pendente`, `gerado`, `enviado`, `duplicado`, `erro`);
- provedor e referencia externa (quando houver);
- operador e data/hora da tentativa;
- documento associado (link para PDF, quando houver);
- detalhe de erro (quando houver falha).

## Aprovacao externa
Cada orcamento possui link publico com token:
- o cliente pode aprovar;
- o cliente pode rejeitar com motivo;
- as acoes ficam registradas no historico.
