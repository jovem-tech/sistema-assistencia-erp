# Correção de Navegação do Botão Voltar

## Contexto
O botão "Voltar" existia em várias telas, mas apontava para rotas fixas, resultando em retorno incorreto em fluxos multi-etapas (ex.: lista → visualizar → editar → voltar).

## Solução aplicada
- Criada função global `goBack()` em `public/assets/js/scripts.js`.
- Adicionado handler delegado para qualquer elemento com `data-back-default`.
- O comportamento agora:
  - Se existir histórico válido no mesmo domínio, usa `history.back()`.
  - Caso contrário, redireciona para o fallback do módulo (`data-back-default`).

## Padrão de uso
```html
<a href="<?= base_url('os') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('os') ?>">
  Voltar
</a>
```

## Ajuda (mesma aba)
Para preservar o histórico e permitir o retorno correto, a ajuda deve abrir na mesma aba.
Evite `window.open()` e `target="_blank"`.

Exemplo:
```html
<button type="button" class="btn btn-outline-info" onclick="window.openDocPage('ordens-de-servico')">
  Ajuda
</button>
```

## Arquivos impactados
- `public/assets/js/scripts.js`
- `app/Views/os/show.php`
- `app/Views/os/form.php`
- `app/Views/clientes/show.php`
- `app/Views/clientes/form.php`
- `app/Views/equipamentos/show.php`
- `app/Views/equipamentos/form.php`
- `app/Views/servicos/form.php`
- `app/Views/usuarios/form.php`
- `app/Views/funcionarios/form.php`
- `app/Views/fornecedores/form.php`
- `app/Views/estoque/form.php`
- `app/Views/estoque/movimentacoes.php`
- `app/Views/financeiro/form.php`
- `app/Views/grupos/form.php`
- `app/Views/grupos/permissoes.php`
- `app/Views/equipamentos_defeitos/form.php`
- `app/Views/relatorios/view_os.php`
- `app/Views/relatorios/view_financeiro.php`
- `app/Views/relatorios/view_estoque.php`
- `app/Views/documentacao/index.php`
- `public/assets/js/scripts.js`
