# Manual do Usuário ? Equipamentos

## ? Visão Geral

Os Equipamentos são os aparelhos cadastrados no sistema, sempre vinculados a um cliente. Cada equipamento tem ficha técnica, fotos e histórico de OS.

---

## ? Cadastrar Novo Equipamento

**Caminho:** OPERACIONAL ? Aparelhos / Equip. ? `+ Novo`

### Campos

| Campo | Obrigatório | Descrição |
|-------|-------------|-----------|
| **Tipo** | Sim | Ex: Smartphone, Notebook, Tablet |
| **Marca** | Sim | Ex: Samsung, Apple, Dell |
| **Modelo** | Sim | Ex: Galaxy S21, iPhone 15 |
| Cliente | Não | Vincular a um cliente existente ou criar novo no botão `+ Novo` |
| Nº de Série | Não | IMEI ou código de série |
| Senha de Acesso | Não | Alternância entre **PIN** e **Alfabeto** |
| Cor | Não | Seleção por catálogo profissional ou detecção por foto |
| Estado Físico | Não | Descrição detalhada de danos |
| Acessórios | Não | Clique nos **botões de atalho** para inserir rapidamente: Carregador, Cabo USB, Capa, Chip ou Cartão de Memória. |

---

## ?? Organização em Abas (Tabs)

Para facilitar o preenchimento, o formulário agora é dividido em 3 seções:

1.  **Informações**: Dados de identificação, cliente, série, senha e acessórios.
2.  **Cor**: Catálogo organizado por famílias de tons e detecção inteligente.
3.  **Fotos**: Inclusão de até 4 arquivos com suporte a editor de corte.

### Foto de Perfil do Equipamento
1. Clique em **`? Tirar Foto`** ? abre câmera do dispositivo
2. Ou clique em **`?? Galeria`** ? selecione arquivo do computador
3. **Editor de imagem abre automaticamente** ? recorte, rotacione e ajuste
4. Confirme o corte com **"Finalizar Corte"**
5. Se o editor de corte nÃ£o estiver disponÃ­vel no navegador, o sistema usa fallback automÃ¡tico para nÃ£o bloquear a inclusÃ£o da foto.
6. Ao selecionar vÃ¡rias imagens na galeria, o sistema processa as fotos em sequÃªncia atÃ© o limite de 4 arquivos por equipamento.

### Organização automática das fotos no servidor
- Cada equipamento salva as fotos em pasta própria dentro de `public/uploads/equipamentos_perfil/`.
- O nome da pasta segue o padrão: `modelo-do-equipamento-nome_do_cliente`.
- Regras do nome:
  - Modelo: minúsculo, sem acentos, espaços viram `-`.
  - Cliente: minúsculo, sem acentos, espaços viram `_`.
  - Com vários clientes vinculados, os nomes são concatenados com `-`.
- As fotos usam numeração incremental na pasta: `perfil_1`, `perfil_2`, `perfil_3`, `perfil_4`.
- Esse comportamento vale em cadastro, edição, substituição e exclusão.

---

### ? Auto-preenchimento via Internet (Ponte de Modelos)
Ao começar a digitar o **Modelo** (com a Marca já selecionada), o sistema oferecerá:
1. **Modelos Cadastrados**: Itens que já existem na sua base local.
2. **Sugestões da Internet**: Modelos reais buscados em APIs globais (Ex: Mercado Livre/Icecat).

**Vantagens:**
- Ao selecionar uma sugestão da internet, o sistema **auto-cadastra** o modelo na sua base de forma limpa (sem redundâncias do tipo e marca).
- Ao clicar no botão `+ Novo` Modelo e preencher, a mesma busca inteligente vai lhe oferecer sugestões para garantir que a ortografia esteja perfeita.
- Reduz drasticamente a digitação de modelos duplicados ou com nomes fora do padrão.

> ? Marcas e Modelos cadastrados ficam instantaneamente disponíveis para todos os futuros cadastros.

---

## ? Seletor de Cor Profissional e Detecção Inteligente

Nosso sistema conta com um seletor de cor inspirado em configuradores modernos (Apple/Samsung), garantindo precisão técnica e visual:

1.  **Preview Grande**: Veja a cor selecionada em um mostrador grande que exibe o nome técnico, código HEX e valores RGB.
2.  **Catálogo de Cores Realistas**: Escolha entre mais de 130 cores comerciais reais (ex: *Midnight, Titanium, Starlight, Sierra Blue, Graphite, Grafite, Prata, etc*), organizadas por categorias.
3.  **Identificação Automática**: Ao usar o seletor manual ou digitar um código HEX, o sistema identifica automaticamente a cor mais próxima no catálogo profissional e sugere o nome correto.
4.  **Sugestões por Foto**: 
    - Envie a foto do aparelho ou tire uma nova.
    - Após o corte (`Crop`), o sistema escaneia a imagem e sugere a cor predominante.
    - Clique em **`Aplicar`** para preencher instantaneamente todos os dados técnicos de cor.
5.  **Cores Próximas**: Ao selecionar uma cor, o sistema exibe automaticamente tons semelhantes para facilitar o ajuste fino.

**Dica:** O técnico pode sempre editar manualmente o nome da cor sugerida para adicionar detalhes específicos.

---

## Responsividade da tela de equipamentos

Melhorias aplicadas:

- A tela de cadastro/edicao agora usa wrapper responsivo (`equip-form-page ds-form-layout`).
- As abas (`Informacoes`, `Cor`, `Fotos`) suportam rolagem horizontal em telas menores.
- Acoes da area de fotos (`Capturar`, `Galeria`) reorganizam para coluna no mobile.
- Grade de miniaturas e rodape de acoes do formulario se ajustam automaticamente por breakpoint.

## ? Visualizar Equipamento

Na tela de detalhes do equipamento você encontra:
- **Fotos** com galeria de miniaturas e zoom
- **Ficha técnica** (tipo, marca, modelo, série, cor, estado)
- **Histórico de OS** vinculadas ao equipamento

---

## ? Vincular a Cliente

Um equipamento pode ser vinculado a um cliente na tela de detalhes:
- Botão **"Vincular Cliente"**
- O equipamento aparecerá no perfil do cliente e nas seleções de OS

---

## ?? Categorias Auxiliares

**Caminho:** OPERACIONAL ? Aparelhos / Equip. ? submenu

| Item | Função |
|------|--------|
| **Tipos** | Gerencia os tipos (Smartphone, Notebook...) |
| **Marcas** | Lista e cadastra marcas |
| **Modelos** | Lista e cadastra modelos por marca |
| **Base de Defeitos** | Problemas comuns por tipo de equipamento |
