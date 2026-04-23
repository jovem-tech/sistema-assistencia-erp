# Correcao: validacao da aba Info no modal de equipamento da OS

Data: 2026-03-24
Modulo: Ordens de Servico / Equipamentos
Arquivos principais:
- `app/Views/os/form.php`
- `app/Controllers/Equipamentos.php`

## Problema
- Ao tentar concluir o cadastro rapido de equipamento pela aba `Foto`, o modal podia exibir erros crus de validacao, como `The tipo_id field is required.`
- Isso transmitia a impressao de tela quebrada, porque a pendencia real estava na aba `Info`.

## Correcao aplicada
- O frontend passou a validar `Tipo`, `Marca` e `Modelo` antes do envio.
- Se algum desses campos estiver vazio, o modal volta automaticamente para a aba `Info`, destaca o campo pendente e exibe mensagem clara em portugues.
- O backend AJAX tambem passou a traduzir esses erros e retornar `focus_tab: info` quando a pendencia estiver na primeira aba.

## Resultado esperado
- Inserir foto nao deixa mais o modal em estado confuso.
- Ao salvar com pendencias de cadastro basico, o usuario e guiado corretamente para a aba `Info`.
