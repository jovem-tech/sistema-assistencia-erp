# Detecção Inteligente de Cor do Equipamento (Baseado em Foto)

**Data de Lançamento:** Março de 2026

## 🎯 Objetivo
Transformar o seletor de cor manual do equipamento em uma ferramenta proativa e inteligente ("Apple Genius Bar level"). O sistema agora analisa a foto principal do equipamento que o técnico carrega, processa os pixels para identificar a cor predominante e combina com nomes "Premium" de mercado automaticamente.

## 🚀 Como Funciona
A funcionalidade está implementada nos módulos onde o equipamento é criado:
1. Modal "Novo Equipamento" na Nova OS (`os/form.php`).
2. Tela de Cadastro de Equipamentos Avulsos (`equipamentos/form.php`).

### Fluxo de Reconhecimento:
1. **Upload da Foto:** O técnico tira foto ou seleciona da galeria.
2. **Cropper Intercept:** No clique de confirmar o "Corte" da foto (antes de salvar e fazer upload pro servidor), ocorre a verificação no canvas local.
3. **Análise de Frequência de Cor:** 
   - A função `detectDominantColor()` processa estritamente os **40% centrais** da imagem (zona mais provável do chassi do aparelho).
   - Ignora fundos puramente brancos ou estourados.
   - Aplica um peso mínimo a pretos muito intensos (normalmente associados a display apagado ou lentes de câmeras) para não distorcer o resultado em telas escuras.
4. **Quantização de Imagem JS:** Transforma o valor de RGB central e aplica arredondamento de tons para identificar o "balde" de cor (bucket) com mais hits na matriz de pixels analisados.
5. **Dicionário Premium de Cores:** Em posse da cor RGB mais popular e relevante do centro da imagem, o algoritmo testa contra o nosso dicionário preestabelecido de Cores Base:
   > Midnight, Starlight, Titanium, Graphite, Phantom Black, Sierra Blue, Pacific Blue, Alpine Green, Rose Gold e afins.
6. **Interface Proativa:**
   - Uma caixa discreta aparece em cima do campo de cor revelando: "Detectado na foto: [■] CorXYZ", e o botão **"Aplicar"**.
   - Ao aplicar, o campo de input original recebe a string Premium traduzida perfeitamente, garantindo busca de relatório coesa e o HexColor preenchido.

## 👨‍💻 Arquitetura (Client-Side)
A mágica acontece **100% no navegador (Client-Side via Javascript HTML5 Canvas)**. 
Isto significa:
- 0 Processamento do PHP/Servidor
- Milissegundos de delay. Não trava o fechamento do crop.
- Totalmente resiliente e offline, respeitando nossa regra de não-dependência estrita do servidor.

## 🗄️ Pontos de Manutenção Futura
Se novos *releases* de cor das marcas bombarem no mercado de hardware, o dicionário responsável deve ser atualizado. Ele fica fixo nos scripts nas variaveis:
- `smartColorMap` (no script de Equipamentos)
- `smartColorMapOS` (no script de Nova OS)
