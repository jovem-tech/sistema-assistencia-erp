# Ordens de Servico

## Capacidades atuais

- listar OS;
- pesquisar OS por busca inteligente;
- abrir visualizacao detalhada;
- iniciar abertura de nova OS;
- selecionar cliente e equipamento;
- registrar relato, prioridade, status, datas e observacoes;
- registrar acessorios estruturados;
- registrar checklist de entrada por tipo de equipamento;
- registrar fotos de entrada;
- cadastrar equipamento durante a abertura quando necessario.

## Regras de preenchimento

- o cliente deve existir ou ser criado no fluxo;
- o equipamento deve pertencer ao cliente selecionado;
- fotos e crop devem seguir o padrao oficial do app;
- acessorios devem ser registrados como itens estruturados, nao como texto solto.
- checklist depende do equipamento selecionado e usa o modelo configurado no ERP.

## Comportamentos obrigatorios

- miniaturas clicaveis para preview;
- feedback visual imediato apos adicionar, remover ou alterar itens;
- formularios sem perda de contexto ao abrir modais.

## Guias detalhados

- listagem: `os-listagem.md`
- abertura: `os-abertura-completa.md`
- detalhe e edicao: `os-detalhe-e-edicao.md`
