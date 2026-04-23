# Sistema de Seletor de Cor Profissional

Implementado em: 2026-03
Status: Ativo
Localização: `/equipamentos/novo` e modal de equipamentos na `/os/novo`

## Descrição
Evolução do seletor de cor simples para um sistema profissional inspirado em configuradores modernos (Apple/Samsung). O sistema foca em precisão visual para técnicos, permitindo identificar cores exatas de fabricantes.

## Características Principais
1. **Preview em Tempo Real**: Box visual grande com contraste automático de texto (preto/branco) dependendo da luminância da cor.
2. **Catálogo de 130+ Cores em Accordion**: Organizado por famílias de tons (Neutras, Azuis, Verdes, etc) com sistema de expansão inteligente.
3. **Swatches Ampliados**: Indicadores visuais de cor de 26px para fácil identificação e escolha.
4. **Algoritmo de Proximidade**: Utiliza distância Euclidiana em espaço RGB para encontrar a cor mais próxima do catálogo quando o usuário usa o picker manual.
5. **Cores Semelhantes**: Sugestões dinâmicas de cores no mesmo espectro da cor selecionada.
6. **Integração com Foto**: Detecta a cor predominante da imagem (focando nos 40% centrais) e mapeia para o nome técnico correspondente.

## Dados Armazenados
Os seguintes campos são persistidos na tabela `equipamentos`:
- `cor_hex`: Código hexadecimal (ex: #1C1C1E)
- `cor_rgb`: Valores decimais (ex: 28,28,30)
- `cor`: Nome comercial (ex: Midnight)

## Regras de Negócio
- A cor sugerida por foto não é obrigatória.
- O nome da cor pode ser editado manualmente pelo técnico se necessário.
- O seletor manual (color picker) sempre tenta "travar" na cor mais próxima do catálogo para manter a padronização.
