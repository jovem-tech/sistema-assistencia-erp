# Módulo: Ordens de Serviço

## Finalidade

Gerenciar todo o ciclo de vida de um reparo: entrada, diagnóstico, orçamento, aprovação, execução e entrega.

## Tabelas Utilizadas

| Tabela | Papel |
|--------|-------|
| `os` | Principal |
| `os_fotos` | Fotos de entrada tiradas no recebimento |
| `os_defeitos` | Defeitos selecionados da Base de Defeitos |
| `os_itens` | Peças e serviços adicionados à OS |

## Relacionamentos

```
os → clientes     (cliente_id)
os → equipamentos (equipamento_id)
os → usuarios     (tecnico_id)
os → os_fotos     (os_id)
os → os_defeitos  (os_id)
os → os_itens     (os_id)
```

## Controller

`app/Controllers/Os.php`

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET /os | Listagem |
| `create()` | GET /os/nova | Formulário novo |
| `store()` | POST /os/salvar | Salva + cria número OS automático |
| `edit($id)` | GET /os/editar/{id} | Formulário edição |
| `update($id)` | POST /os/atualizar/{id} | Atualiza |
| `show($id)` | GET /os/visualizar/{id} | Detalhes completos |
| `print($id)` | GET /os/imprimir/{id} | Versão de impressão |
| `updateStatus($id)` | POST /os/status/{id} | Atualiza status via AJAX |
| `addItem()` | POST /os/item/salvar | Adiciona peça/serviço à OS |
| `removeItem($id)` | GET /os/item/excluir/{id} | Remove item |

## Fluxo de Status

```
abertura → aguardando_analise
           ↓
      aguardando_orcamento
           ↓
      aguardando_aprovacao ──→ reprovado
           ↓
         aprovado
           ↓
        em_reparo ←─→ aguardando_peca
           ↓
          pronto
           ↓
         entregue
```

## Número da OS

Gerado automaticamente no padrão: `OS-AAAA-NNNNN`
Ex: `OS-2026-00042`

## Link de Orçamento

Gerado um token único (`token_orcamento`) que permite o cliente aprovar/recusar via URL pública sem login:
```
/orcamento/{token}
```

## Permissões Requeridas

`visualizar`, `criar`, `editar`, `excluir`
