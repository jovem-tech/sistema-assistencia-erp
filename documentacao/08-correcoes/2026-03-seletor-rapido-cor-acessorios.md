# Correção: Seletor rápido de cor para acessórios na OS

## Contexto
No registro de acessórios da tela `OS > Nova`, o picker padrão exigia ajuste manual para cores recorrentes do balcão.

## Ajuste aplicado
- Adicionado um conjunto de seleção rápida com 12 cores comuns no formulário de acessórios (capinha, mochila e bolsa).
- As opções rápidas preenchem automaticamente o nome da cor no campo do acessório.
- Mantido o picker nativo para ajuste fino quando necessário.
- Comportamento responsivo:
  - Desktop: cores rápidas exibidas lado a lado em linha única.
  - Mobile: cores agrupadas em um botão clicável `Cores rápidas`.
- Ajuste de layout aplicado para evitar corte visual:
  - Campo de cor ocupa largura total do formulário rápido.
  - Botões rápidos em tamanho compacto para exibir as 12 opções sem truncamento no desktop.

## Paleta rápida
- Preto
- Marrom
- Azul claro
- Verde claro
- Rosa
- Vermelho
- Laranja
- Amarelo
- Verde
- Azul
- Roxo/Violeta
- Branco

## Arquivo impactado
- `app/Views/os/form.php`
