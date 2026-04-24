# Correcao: fallback de foto da OS com `data:` URI

Data: 24/04/2026  
Status: aplicado no ERP web

## Problema

Na tela de edicao da OS (`/os/editar/{id}`), a sidebar de fotos do equipamento e o modal de fotos podiam quebrar quando o backend retornava um fallback inline em `data:image/svg+xml;base64,...`.

O JavaScript da view aplicava anti-cache com `?v=timestamp` em qualquer valor recebido, inclusive em `data:` URI. Isso gerava `src` invalido no navegador e resultava em:

- miniaturas quebradas;
- imagem principal quebrada;
- erro `Failed to load resource: net::ERR_INVALID_URL` no console.

## Causa raiz

O helper `withFotoVersion()` em `app/Views/os/form.php` foi criado para evitar cache visual em fotos reais do equipamento, mas nao diferenciava:

- URLs HTTP/relativas servidas pelo ERP;
- fallbacks inline em `data:`;
- blobs temporarios de preview/crop em `blob:`.

## Correcao aplicada

- `withFotoVersion()` agora retorna o valor original quando a origem comeca com `data:` ou `blob:`;
- o anti-cache `?v=timestamp` continua ativo apenas para URLs reais de arquivo;
- hashes (`#...`) continuam preservados quando existirem.

## Impacto operacional

- a foto principal da sidebar volta a renderizar corretamente quando a imagem fisica nao existe;
- miniaturas e lightbox continuam reativos sem quebrar o fallback inline;
- a estrategia anti-cache permanece valida para uploads reais apos inserir, excluir ou definir foto principal.
