# Fluxo de Autenticação

## Visão Geral

```
[Browser] ──GET /dashboard──► [AuthFilter]
                                    │ sem sessão?
                                    ▼
                              redirect /login
                                    │ com sessão?
                                    ▼
                            [PermissionFilter]
                                    │ sem permissão?
                                    ▼
                               403 Forbidden
                                    │ com permissão?
                                    ▼
                            [Controller::method()]
```

---

## Login (`Auth::attemptLogin`)

```
1. Usuário preenche email + senha em /login
2. POST → Auth::attemptLogin()
3. Busca usuário pelo email no banco
4. Verifica se usuário está ativo
5. Verifica password_verify($senha, $hash)
6. Se válido:
   - Cria sessão com: user_id, user_name, user_email, user_group_id
   - Registra log de acesso
   - Redireciona para /dashboard
7. Se inválido:
   - Incrementa tentativas falhas
   - Retorna com mensagem de erro
```

---

## `AuthFilter.php`

Executado em **todas** as rotas do grupo protegido.

```php
// Verifica se existe sessão ativa
if (!session()->get('user_id')) {
    return redirect()->to('/login');
}
```

---

## `PermissionFilter.php`

Executado nas rotas com `['filter' => 'permission:modulo:acao']`.

```php
// Busca permissões do grupo do usuário logado
// Verifica se 'modulo:acao' está na lista de permissões
// Se não: responde com 403
```

---

## Helpers de Permissão (`sistema_helper.php`)

```php
// Verifica se o usuário tem permissão (retorna bool)
can('clientes', 'criar')          // true ou false

// Bloqueia acesso e redireciona com erro se não tem permissão
requirePermission('clientes')

// Obtém usuário logado
current_user()                     // array com dados do usuário
```

---

## Sessão

Dados armazenados na sessão de cada usuário:

| Chave | Valor |
|-------|-------|
| `user_id` | ID do usuário |
| `user_name` | Nome para exibição |
| `user_email` | Email de login |
| `user_group_id` | ID do grupo de permissões |

---

## Logout

```
GET /logout → Auth::logout()
  → session()->destroy()
  → redirect /login
```

---

## Recuperação de Senha

```
1. GET  /esqueci-senha → formulário de e-mail
2. POST /esqueci-senha → gera token único + envia e-mail
3. GET  /redefinir-senha/{token} → formulário de nova senha
4. POST /redefinir-senha/{token} → atualiza senha + invalida token
```
