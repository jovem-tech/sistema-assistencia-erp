# Fluxo de Autenticacao

## Visao Geral

```text
[Browser] --GET /dashboard--> [AuthFilter]
                                | sem sessao?
                                v
                          redirect /login
                                | com sessao?
                                v
                        [PermissionFilter]
                                | sem permissao?
                                v
                           403 Forbidden
                                | com permissao?
                                v
                        [Controller::method()]
```

---

## Login (`Auth::attemptLogin`)

```text
1. Usuario preenche email + senha em /login
   - a tela exibe a versao atual via get_system_version()
2. POST -> Auth::attemptLogin()
3. Busca usuario pelo email no banco
4. Verifica se usuario esta ativo
5. Verifica password_verify($senha, $hash)
6. Se valido:
   - Cria sessao com: user_id, user_name, user_email, user_group_id
   - Registra log de acesso
   - Redireciona para /dashboard
7. Se invalido:
   - Incrementa tentativas falhas
   - Retorna com mensagem de erro
```

---

## `AuthFilter.php`

Executado em **todas** as rotas do grupo protegido.

```php
// Verifica se existe sessao ativa
if (!session()->get('user_id')) {
    return redirect()->to('/login');
}
```

---

## `PermissionFilter.php`

Executado nas rotas com `['filter' => 'permission:modulo:acao']`.

```php
// Busca permissoes do grupo do usuario logado
// Verifica se 'modulo:acao' esta na lista de permissoes
// Se nao: responde com 403
```

---

## Helpers de Permissao (`sistema_helper.php`)

```php
// Verifica se o usuario tem permissao (retorna bool)
can('clientes', 'criar')          // true ou false

// Bloqueia acesso e redireciona com erro se nao tem permissao
requirePermission('clientes')

// Obtem usuario logado
current_user()                     // array com dados do usuario
```

---

## Sessao

Dados armazenados na sessao de cada usuario:

| Chave | Valor |
|-------|-------|
| `user_id` | ID do usuario |
| `user_name` | Nome para exibicao |
| `user_email` | Email de login |
| `user_group_id` | ID do grupo de permissoes |

### Heartbeat de sessao

- O frontend protegido usa `public/assets/js/scripts.js` para monitorar inatividade.
- O endpoint `GET /sessao/heartbeat` responde em JSON e agora fecha a sessao logo apos ler os dados necessarios, reduzindo contencao no driver de sessao por arquivo.
- O `AuthFilter` reconhece o heartbeat e:
  - atualiza `last_activity` de forma controlada;
  - fecha a sessao imediatamente depois da validacao nesse fluxo;
  - evita manter lock desnecessario durante a resposta.
- O frontend tambem passou a:
  - nao disparar heartbeat enquanto houver `fetch` ou `$.ajax` same-origin em andamento;
  - aguardar 5 segundos apos trafego same-origin antes de enviar novo heartbeat;
  - abortar o heartbeat em 10 segundos para evitar requests presos indefinidamente.

---

## Exibicao de versao na autenticacao

- A view `app/Views/auth/login.php` exibe a versao oficial do sistema antes do login.
- A fonte da versao e o helper `get_system_version()`.
- O helper respeita:
  - valor padrao em `app/Config/SystemRelease.php`
  - override opcional em `configuracoes.sistema_versao`

---

## Logout

```text
GET /logout -> Auth::logout()
  -> session()->destroy()
  -> redirect /login
```

---

## Recuperacao de Senha

```text
1. GET  /esqueci-senha -> formulario de e-mail
2. POST /esqueci-senha -> gera token unico + envia e-mail
3. GET  /redefinir-senha/{token} -> formulario de nova senha
4. POST /redefinir-senha/{token} -> atualiza senha + invalida token
```
