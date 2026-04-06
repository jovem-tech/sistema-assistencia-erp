# Padroes de Fotos e Uploads

Atualizado em 06/04/2026.

## Regra unica

Todo fluxo de foto do app deve seguir a mesma trilha:

1. escolher origem
2. abrir crop
3. confirmar corte
4. renderizar miniatura
5. permitir preview ampliado
6. permitir remocao
7. enviar para o backend no formato esperado

## Contextos atuais

- equipamento
- acessorio
- fotos de entrada
- galeria de perfil na listagem de OS

## Persistencia

- fotos de equipamento: `uploads/equipamentos_perfil`
- fotos de acessorio: `uploads/acessorios/OS_<slug>/`
- fotos de entrada: `uploads/os_anormalidades`

## Regras

- maximo de 4 fotos nos fluxos documentados
- miniatura clicavel deve abrir preview
- o botao de remocao nao pode conflitar com o clique de preview
- inicializacao do crop nao deve depender de `import()` dinamico (evita falha intermitente de chunk em ambiente local)
- ao falhar a abertura do crop, limpar estado interno completo para nao travar proxima tentativa
- aceitar imagem por MIME (`image/*`) e por extensao conhecida quando o MIME vier vazio no arquivo
