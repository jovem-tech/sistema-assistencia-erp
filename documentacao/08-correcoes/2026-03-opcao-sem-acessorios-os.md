# Correcao: Opcao Sem Acessorios na Abertura da OS

## Contexto
Na tela `/os/nova`, o registro de acessorios exigia adicionar itens manualmente ou deixar o campo vazio, sem uma marcacao explicita para entradas em que o equipamento chega sem nenhum acessorio.

## Correcao aplicada
- Adicionado o controle `Equipamento recebido sem acessorios` no bloco **Acessorios e Componentes (na entrada)**.
- Ao marcar essa opcao, o sistema passa a registrar `Sem acessorios`.
- Se ja existirem itens na lista, o sistema solicita confirmacao antes de limpar os acessorios cadastrados.
- Com a opcao ativa, os botoes de adicao rapida de acessorios ficam desabilitados para evitar inconsistencias.
- A validacao de pendencias da abertura da OS considera a marcacao como preenchimento valido de acessorios.

## Resultado esperado
- Fluxo de recepcao mais rapido quando nao ha itens acompanhando o equipamento.
- Registro explicito no historico da OS, evitando ambiguidade de campo vazio.

## Arquivos impactados
- `app/Views/os/form.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
