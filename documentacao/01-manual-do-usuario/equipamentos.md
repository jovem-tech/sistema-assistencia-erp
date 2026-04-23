# Manual do Usuario - Equipamentos

## Visao Geral

Os equipamentos sao os aparelhos cadastrados no sistema, sempre vinculados a um cliente. Cada equipamento tem ficha tecnica, fotos e historico de OS.

---

## Cadastrar Novo Equipamento

**Caminho:** `Operacional > Aparelhos / Equip. > + Novo`

### Campos

| Campo | Obrigatorio | Descricao |
|---|---|---|
| Tipo | Sim | Ex.: Smartphone, Notebook, Tablet |
| Marca | Sim | Ex.: Samsung, Apple, Dell |
| Modelo | Sim | Ex.: Galaxy S21, iPhone 15 |
| Cliente | Nao | Vincular a um cliente existente ou criar novo no botao `+ Novo` |
| Numero de serie | Nao | IMEI ou codigo de serie |
| Senha de acesso | Nao | Alternancia entre `DESENHO` (padrao Android 3x3) e `TEXTO` |
| Cor | Nao no cadastro completo | Selecao por catalogo profissional ou deteccao por foto |
| Estado fisico | Nao | Descricao detalhada de danos |
| Acessorios | Nao | Atalhos para itens comuns, como carregador, cabo USB, capa, chip e cartao de memoria |

---

## Organizacao em Abas

Para facilitar o preenchimento, o formulario e dividido em 3 secoes:

1. `Informacoes`: dados de identificacao, cliente, serie, senha e acessorios.
2. `Cor`: catalogo organizado por familias de tons e deteccao inteligente.
3. `Fotos`: inclusao de ate 4 arquivos com suporte a editor de corte.

### Marca e Modelo com cadastro contextual

- Os campos `Marca` e `Modelo` exibem o botao verde `+ Adicionar` ao lado da label.
- O botao abre os modais de cadastro rapido sem tirar o tecnico do formulario principal.
- O item salvo entra imediatamente na selecao atual.

### Senha de Acesso em padrao Android

- Modo `DESENHO`:
  - mostra uma grade 3x3 de pontos
  - a area do desenho e compacta, sem ocupar largura excessiva do formulario
  - o tecnico passa o mouse pelos pontos para formar a sequencia
  - o valor salvo no banco segue o padrao `desenho_1-4-7-8-9`
- Modo `TEXTO`:
  - aceita senha tradicional do aparelho
- O campo `Nº Série ou IMEI` segue label normalizada e sem caracteres corrompidos.

### Foto de Perfil do Equipamento

1. Clique em `Tirar Foto` para abrir a camera do dispositivo.
2. Ou clique em `Galeria` para selecionar arquivo do computador.
3. O editor de imagem abre automaticamente para recorte e ajustes.
4. Confirme o corte em `Finalizar Corte`.
5. Se o editor de corte nao estiver disponivel no navegador, o sistema usa fallback automatico para nao bloquear a inclusao da foto.
6. Ao selecionar varias imagens na galeria, o sistema processa as fotos em sequencia ate o limite de 4 arquivos por equipamento.

### Cadastro rapido pela Ordem de Servico

- No modal rapido de novo equipamento aberto dentro da `Nova OS`, o sistema exige obrigatoriamente:
  - cor correta do aparelho
  - ao menos uma foto do equipamento
- Se o tecnico tentar salvar sem um desses itens, o envio e bloqueado.
- O modal abre automaticamente a aba pendente (`Cor` ou `Foto`) e posiciona o foco no campo que falta concluir.

### Organizacao automatica das fotos no servidor

- Cada equipamento salva as fotos em pasta propria dentro de `public/uploads/equipamentos_perfil/`.
- O nome da pasta segue o padrao `modelo-do-equipamento-nome_do_cliente`.
- Regras do nome:
  - modelo em minusculo, sem acentos, com espacos convertidos em `-`
  - cliente em minusculo, sem acentos, com espacos convertidos em `_`
  - com varios clientes vinculados, os nomes sao concatenados com `-`
- As fotos usam numeracao incremental na pasta: `perfil_1`, `perfil_2`, `perfil_3`, `perfil_4`.
- Esse comportamento vale em cadastro, edicao, substituicao e exclusao.

---

## Auto-preenchimento via Internet

Ao comecar a digitar o modelo, com a marca ja selecionada, o sistema oferece:

1. `Modelos cadastrados`: itens que ja existem na base local.
2. `Sugestoes da internet`: modelos reais buscados em APIs globais, como Mercado Livre e Icecat.

### Vantagens

- Ao selecionar uma sugestao da internet, o sistema auto-cadastra o modelo na base de forma limpa, sem redundancias de tipo e marca.
- Ao clicar no botao `+ Novo` em modelo e preencher, a mesma busca inteligente oferece sugestoes para manter a ortografia consistente.
- Isso reduz duplicacao e nomes fora do padrao.

> Marcas e modelos cadastrados ficam instantaneamente disponiveis para os proximos cadastros.

---

## Seletor de Cor Profissional e Deteccao Inteligente

O sistema usa um seletor de cor inspirado em configuradores modernos para melhorar precisao tecnica e visual:

1. `Preview grande`: mostra nome tecnico, codigo HEX e valores RGB.
2. `Catalogo de cores realistas`: mais de 130 cores comerciais reais, organizadas por categorias.
3. `Identificacao automatica`: ao usar o seletor manual ou digitar um HEX, o sistema identifica a cor mais proxima no catalogo e sugere o nome correto.
4. `Sugestoes por foto`:
   - envie a foto do aparelho ou tire uma nova
   - apos o corte, o sistema escaneia a imagem e sugere a cor predominante
   - clique em `Aplicar` para preencher os dados tecnicos da cor
5. `Cores proximas`: ao selecionar uma cor, o sistema exibe tons semelhantes para ajuste fino.

O tecnico pode editar manualmente o nome da cor sugerida para adicionar detalhes especificos.

---

## Responsividade da Tela de Equipamentos

Melhorias aplicadas:

- wrapper responsivo `equip-form-page ds-form-layout`
- abas `Informacoes`, `Cor` e `Fotos` com rolagem horizontal em telas menores
- acoes da area de fotos reorganizadas para coluna no mobile
- grade de miniaturas e rodape de acoes ajustados automaticamente por breakpoint

---

## Visualizar Equipamento

Na tela de detalhes do equipamento voce encontra:

- fotos com galeria de miniaturas e zoom
- ficha tecnica com tipo, marca, modelo, serie, cor e estado
- historico de OS vinculadas ao equipamento

---

## Vincular a Cliente

Um equipamento pode ser vinculado a um cliente na tela de detalhes:

- botao `Vincular Cliente`
- o equipamento passa a aparecer no perfil do cliente e nas selecoes de OS

---

## Categorias Auxiliares

**Caminho:** `Operacional > Aparelhos / Equip. > submenu`

| Item | Funcao |
|---|---|
| Tipos | Gerencia os tipos, como Smartphone e Notebook |
| Marcas | Lista e cadastra marcas |
| Modelos | Lista e cadastra modelos por marca |
| Base de Defeitos | Problemas comuns por tipo de equipamento |

---

## Qualidade visual de textos

- As abas `Informações`, `Cor` e `Fotos` foram revisadas para eliminar caracteres estranhos na renderizacao.
- Labels como `Observações Internas` e textos auxiliares de cor/foto agora seguem UTF-8 consistente.
- Os cabecalhos de sugestoes do cadastro de equipamento tambem foram normalizados para evitar simbolos quebrados.
