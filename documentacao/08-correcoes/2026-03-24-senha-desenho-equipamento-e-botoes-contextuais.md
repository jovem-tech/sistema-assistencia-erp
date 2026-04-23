# Correcao - senha por desenho e acoes contextuais em equipamento

## Data
2026-03-24

## Escopo
- modal rapido de equipamento em `Nova OS`
- formulario completo de cadastro/edicao de equipamento
- serializacao backend da senha de acesso

## Ajustes aplicados
- os campos `Marca` e `Modelo` passaram a exibir o botao verde `+ Adicionar` ao lado da label
- o label `Nº de Série` foi normalizado no modal rapido
- a `Senha de Acesso` passou a aceitar:
  - `DESENHO`, com grade 3x3 no padrao Android
  - `TEXTO`, para senha tradicional
- a grade de desenho foi reduzida para um bloco compacto, com melhor usabilidade em modal e no formulario completo
- quando o tecnico usa desenho, o valor e salvo em `senha_acesso` no formato `desenho_1-4-7-8-9`
- a normalizacao desse formato foi centralizada no controller de equipamentos para cobrir cadastro completo e endpoints AJAX

## Arquivos alterados
- `app/Views/os/form.php`
- `app/Views/equipamentos/form.php`
- `app/Controllers/Equipamentos.php`
- `public/assets/js/scripts.js`
- `public/assets/css/design-system/layouts/os-form-layout.css`

## Impacto funcional
- reduz atrito no cadastro contextual de marca/modelo
- padroniza a captura de senha por desenho sem depender de texto livre
- garante consistencia de persistencia no banco mesmo se o frontend enviar o valor de formas diferentes
