# Manual do Usuario - Ordens de Servico

## Visao geral
A Ordem de Servico (OS) e o registro principal do atendimento tecnico, da recepcao do equipamento ate a entrega.

### Identificador Unico (Numero da OS)
O sistema utiliza um padrao inteligente para numeracao: **`OSYYMMSSSS`**
- **YY**: Ano (Ex: 26)
- **MM**: Mes (Ex: 03)
- **SSSS**: Sequencia numerica (Ex: 0001)

Este numero e gerado automaticamente e a **sequencia reseta para 0001 no inicio de cada mes**, facilitando a organizacao e o controle de volume.

## Abertura de nova OS
Caminho: `Ordens de Servico > + Nova OS`

### Estrutura por abas
- `Dados`
- `Relato do Cliente`
- `Fotos`

Na aba `Dados`, o formulario foi separado em blocos visuais para reduzir erro de preenchimento:
- Cliente, Equipamento e Tecnico Responsavel
- Prioridade, Data de Entrada, Previsao e Status
- Estado fisico do equipamento
- Acessorios e Componentes (na entrada)

## Campos principais da abertura
| Campo | Obrigatorio | Uso |
|---|---|---|
| Cliente | Sim | Cliente dono da OS |
| Equipamento | Sim | Equipamento vinculado ao cliente |
| Tecnico Responsavel | Nao | Tecnico que assume a OS |
| Prioridade | Sim | Baixa/Normal/Alta/Urgente |
| Data de Entrada | Sim | Data/hora de recebimento |
| Previsao de Entrega | Nao | Data prevista para retorno |
| Status | Sim | Estado inicial da OS |
| Relato do Cliente | Sim | Texto informado na recepcao |
| Estado fisico na entrada | Nao | Danos visuais observados |
| Acessorios na entrada | Nao | Itens recebidos com o equipamento |

## Registro de acessorios
O bloco `Acessorios e Componentes (na entrada)` usa botoes de insercao rapida.

Fluxo:
1. Clique em um botao rapido (`+ Chip`, `+ Capinha celular`, `+ Cabo`, etc.).
2. Preencha campos complementares quando existirem.
3. Salve o item.
4. Edite/remova quando necessario.
5. Adicione fotos por `Galeria` ou `Camera`.

Regras:
- Opcao `Equipamento recebido sem acessorios` marca a entrada sem itens.
- Fotos por acessorio usam crop/preview antes de salvar.
- Se o editor visual nao abrir corretamente, o sistema usa fallback automatico e adiciona a foto sem corte para nao travar a tela.
- Imagens ficam em `uploads/acessorios/OS_<numero_os>/`.

## Registro de estado fisico
O bloco `Estado fisico do equipamento` usa a mesma logica de item dinamico dos acessorios.

Fluxo:
1. Clique em um botao rapido (`+ Tela trincada`, `+ Arranhoes`, `+ Carcaca quebrada`, `+ Outro dano`).
2. Salve o item.
3. Edite/remova o item quando necessario.
4. Adicione fotos por item com `Galeria` ou `Camera`.

Regra especial:
- `Sem avarias aparentes na entrada` substitui os itens cadastrados (com confirmacao).
- Se o editor visual nao abrir corretamente, o sistema usa fallback automatico e adiciona a foto sem corte para nao travar a tela.

Armazenamento de fotos:
- `uploads/estado_fisico/OS_<numero_os>/estado_<slug_os>_<sequencia>.<ext>`

## Relato do cliente
Na abertura da OS, o relato pode ser montado com selecao rapida por categoria.

Fonte dos itens:
- Modulo `Gestao de Conhecimento > Defeitos Relatados`

Comportamento:
- Clicar em uma opcao adiciona a frase no textarea.
- O tecnico pode editar manualmente o texto livremente.

## Aba Fotos (abertura)
O upload de fotos de entrada usa o padrao unico do sistema:
- Galeria
- Camera
- Crop antes de salvar
- Preview com remocao
- Fallback automatico sem corte quando o modal/editor visual falhar

Observacao tecnica de UX:
- A abertura do editor de corte na OS segue o mesmo comportamento do cadastro de equipamentos (`/equipamentos/novo`), para manter consistencia entre os fluxos de foto do sistema.
- A abertura da camera na OS reutiliza o mesmo padrao de modal controlado e, se o navegador bloquear a interface da camera, o sistema informa o motivo por alerta e pelo console.

## Visualizacao da OS
Caminho: `/os/visualizar/{id}`

Agora a OS exibe o estado fisico em dois pontos:
- `Informacoes > Estado fisico na entrada` (descricao + fotos por item)
- `Fotos de Entrada > Fotos do Estado fisico`

Tambem exibe:
- Fotos da entrada geral
- Fotos de acessorios

## Listagem de OS responsiva
Caminho: `/os`

Melhorias aplicadas:
- Filtros superiores reorganizados por breakpoint (desktop/notebook/tablet/mobile) sem sobreposicao.
- Colunas menos criticas sao ocultadas automaticamente conforme largura:
  - ate 1499px: oculta `Relato`
  - ate 1279px: oculta `Valor Total`
  - ate 1023px: oculta `Equipamento`
  - ate 859px: oculta `Data Abertura`
- Em mobile, linhas da tabela viram blocos/cartoes com label por campo.
- Acoes continuam acessiveis no fim do bloco, otimizadas para toque.

## Responsividade da abertura de OS
Caminho: `/os/nova`

Melhorias aplicadas:
- Layout principal padronizado com coluna lateral + formulario (`ds-split-layout`).
- Em notebook: coluna lateral reduzida com formulario mais amplo.
- Em tablet/mobile: empilhamento automatico (lateral acima, formulario abaixo).
- Abas com rolagem horizontal (`ds-tabs-scroll`) para evitar quebra visual.
- Blocos de dados (`os-data-section`) com espacamento ajustado por breakpoint.
- Botoes de acao da OS (`Abrir`, `Cancelar`, `Limpar rascunho`) com comportamento empilhado no mobile.

## Responsividade da visualizacao de OS
Caminho: `/os/visualizar/{id}`

Melhorias aplicadas:
- Estrutura principal convertida para split responsivo (painel de fotos + conteudo).
- Cards de status/PDF/WhatsApp com reorganizacao 1-2-3 colunas por breakpoint.
- Formulario rapido de status com quebra inteligente (sem apertar select e botao).
- Tabs de secoes com navegacao horizontal em telas menores.

## Edicao da OS
Na edicao (`/os/editar/{id}`), os dados de abertura podem ser ajustados e os registros de estado fisico/acessorios sao persistidos novamente.

## Fluxo operacional por macrofases
O status da OS foi padronizado em macrofases:
- recepcao
- diagnostico
- orcamento
- execucao
- interrupcao
- qualidade
- concluido
- finalizado_sem_reparo
- encerrado
- cancelado

## Regras de transicao de status
- O sistema valida transicoes permitidas entre status.
- Mudancas invalidas sao bloqueadas.
- Cada alteracao gera registro no historico da OS com usuario e data/hora.

## Comunicacao WhatsApp na OS
Na tela de visualizacao (`/os/visualizar/{id}`):
- Bloco `WhatsApp` para envio manual por template ou texto livre.
- Opcao de selecionar um PDF ja gerado da OS para enviar junto como anexo.
- Botao rapido de envio WhatsApp em cada documento da lista `Documentos PDF`.
- Historico de envios da OS com status e tipo de conteudo (texto, pdf ou texto+pdf).
- Envio automatico em status-chave (quando configurado): abertura, aguardando autorizacao, aguardando peca, pronto para retirada e entrega.

## Documentos PDF da OS
Na tela de visualizacao (`/os/visualizar/{id}`):
- Bloco `Documentos PDF` para gerar e listar versoes.
- Tipos disponiveis:
  - abertura
  - orcamento
  - laudo
  - entrega
  - devolucao_sem_reparo

Os arquivos ficam em:
- `public/uploads/os_documentos/OS_<numero_os>/`
