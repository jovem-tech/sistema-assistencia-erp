# Correção de Texto e Cor em Acessórios da OS

## Contexto
Na tela `OS > Nova`, o preenchimento de acessórios com cor estava exibindo formatos que não atendiam ao padrão operacional:
- uso de sufixos como `sem cor`;
- separação por hífen;
- exibição do valor hexadecimal junto ao nome.

## Correção aplicada
- Ajustada a montagem do texto dos acessórios para o padrão `descricao + complemento` sem hífen.
- Removido o fallback `sem cor` e `sem tipo` para campos opcionais.
- Mantido o seletor de cor para capinha, mochila e bolsa com conversão para nome de cor por aproximação RGB.
- Padronizada a saída para mostrar **apenas o nome da cor** (sem `#HEX`).
- Adicionada limpeza de legado na renderização para remover `(#HEX)` de itens já existentes na lista em memória da tela.

## Resultado esperado
- Com cor: `Capinha celular Azul Céu`
- Sem cor: `Capinha celular`
- Sem tipo no cabo/carregador: `Cabo` / `Carregador`

## Arquivo impactado
- `app/Views/os/form.php`
