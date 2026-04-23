# Correção: Remoção de 404 de imagens ausentes na VPS

Data: 2026-03-21  
Módulos: OS / Equipamentos / Uploads

## Problema

Em ambiente VPS de desenvolvimento, a pasta `public/uploads` não é sincronizada via Git (intencionalmente, para não subir mídia pesada).  
Com isso, a interface tentava carregar URLs legadas como:

- `/uploads/equipamentos/perfil_1.jpg`
- `/uploads/equipamentos/perfil_2.jpg`
- `/uploads/os/os26030015_entrada_1_1773893896.jpg`

Resultado: múltiplos erros `404 (Not Found)` no console durante renderização de fotos da OS e do equipamento.

## Causa raiz

1. Backend retornava URL legada mesmo quando o arquivo físico não existia.
2. Frontend consumia essa URL diretamente, causando requests quebrados em sequência.

## Solução aplicada

### 1) `app/Controllers/Equipamentos.php`

- Ajuste em `buildFotoPublicUrl()`:
  - mantém tentativa em caminhos válidos (`equipamentos_perfil` e legado `equipamentos`);
  - quando nenhum arquivo existe, retorna **placeholder em Data URI SVG** (inline), evitando request HTTP para arquivo inexistente.
- Adição do helper privado:
  - `missingImageDataUri()`

### 2) `app/Controllers/Os.php`

- `show()` e `edit()` passaram a resolver URL por métodos dedicados, com fallback seguro:
  - `resolveEquipamentoFotoPublicUrl()`
  - `resolveOsEntradaFotoPublicUrl()`
- Quando a mídia não existe fisicamente, retorna o mesmo **placeholder Data URI SVG** (sem gerar 404).
- Adição do helper privado:
  - `missingImageDataUri()`

## Impacto esperado

- Console limpo, sem spam de 404 para fotos ausentes em VPS de desenvolvimento.
- Interface permanece funcional sem necessidade de sincronizar mídia pesada.
- Continuidade da experiência visual com placeholder em vez de imagem quebrada.

## Validação pós-deploy

1. Abrir `OS > Visualizar` e `OS > Editar` em registros com fotos antigas/ausentes.
2. Confirmar no DevTools que não há requests 404 para `/uploads/equipamentos/*` e `/uploads/os/*`.
3. Confirmar que miniaturas/área principal exibem placeholder quando o arquivo não existe.

## Observação operacional

Essa correção é apropriada para ambientes de desenvolvimento/homologação sem sincronismo de mídia.  
Em produção definitiva, recomenda-se storage persistente + rotina de backup de `public/uploads`.

