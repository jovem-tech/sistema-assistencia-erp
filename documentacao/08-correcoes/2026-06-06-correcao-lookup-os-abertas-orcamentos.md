# Correcao do lookup de OS abertas no formulario de Orcamentos

Data: 06/06/2026

## Problema

Ao abrir `Novo Orcamento` com cliente vinculado a OS, a requisicao `GET /orcamentos/os-abertas/cliente` podia falhar com `500 (Internal Server Error)`.

Sintomas observados:

- o card `Vinculo OS` exibia falha ao carregar as OS abertas;
- o console registrava erro em `loadOsAbertasByCliente`;
- o log PHP apontava `Undefined variable $marca` em `App\Controllers\Orcamentos::formatOsAbertaLookupResult()`.

## Causa raiz

O formatter das OS abertas usava `$marca` e `$modelo` ao montar `search_text` e o payload do equipamento, mas essas variaveis nao eram inicializadas a partir do resultado SQL.

Em cenarios com lookup de OS do cliente, isso derrubava a resposta JSON inteira do endpoint.

## Correcao aplicada

- inicializacao explicita de `equip_marca` e `equip_modelo` dentro de `formatOsAbertaLookupResult()`;
- manutencao do payload do Select2 com tolerancia a cadastro parcial do equipamento;
- preservacao do comportamento reativo do card `Vinculo OS`, sem exigir reload manual.

## Impacto funcional

- o endpoint volta a responder com sucesso para clientes com OS abertas;
- a selecao de OS no formulario de Orcamentos deixa de quebrar quando a marca/modelo do equipamento estiver vazia;
- o operador pode continuar o fluxo de criacao do orcamento normalmente.

## Validacao tecnica

- adicionada cobertura de regressao em `tests/unit/OrcamentosLookupTest.php` para garantir que `formatOsAbertaLookupResult()` continue serializando `marca` e `modelo` sem disparar erro PHP;
- mantida a compatibilidade com equipamentos de cadastro parcial e sem foto principal.
