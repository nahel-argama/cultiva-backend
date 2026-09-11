# Quickstart Validation: Compras de Ofertas

## Prerequisites

- Docker Compose com os serviços `php` e `database` do backend.
- Usuários/factories para Producer e Retailer.
- Uma Offer `active` com saldo positivo e token Sanctum com ability `access`.
- Uma Offer criada sem `status` deve nascer `active`; o backend define esse estado.
- Bruno/Postman configurados com `base_url`, `access_token`, `offer_id`, `page` e `per_page`.

Todos os comandos PHP, Artisan, Composer e testes devem ser executados no container `php`. Não executar Laravel Pint.

## Required pre-implementation artifacts

Antes de qualquer código de produção:

1. Conferir [contracts/openapi.yaml](contracts/openapi.yaml).
2. Criar as requisições Bruno para comprar, listar compras e listar vendas.
3. Criar e executar os testes Feature descritos abaixo, confirmando Red pelo comportamento ausente.

Se um teste passar antes da implementação, corrigir o teste para provar a regra ainda ausente antes de continuar.

## TDD commands

Executar cada arquivo novo individualmente no container, antes da produção correspondente:

```bash
docker compose exec php php artisan test --filter=PurchaseController\\StoreTest
docker compose exec php php artisan test --filter=PurchaseController\\PurchaseIndexTest
docker compose exec php php artisan test --filter=PurchaseController\\SaleIndexTest
```

Resultado Red esperado: rota inexistente, contrato/modelo ausente ou comportamento de compra/listagem ainda não implementado — nunca erro de sintaxe, infraestrutura ou banco indisponível.

Depois da implementação:

```bash
docker compose exec php php artisan test --filter=PurchaseController
docker compose exec php php artisan test --filter=OfferController
docker compose exec php php artisan test
```

## Bruno scenarios

As requisições em `collection/bruno/API/Purchases/` devem ser criadas antes da implementação e seguir o contrato:

- `Buy Offer`: `POST /v1/offers/:offer_id/purchase`, token de retailer, body `{ "quantity": 2 }`.
- `Show Available Offer (Retailer)`: `GET /v1/offers/:offer_id`, token de retailer.
- `List My Purchases`: `GET /v1/purchases?page={{page}}&per_page={{per_page}}`, token de retailer.
- `List My Sales`: `GET /v1/sales?page={{page}}&per_page={{per_page}}`, token de producer.

## End-to-end acceptance

- Compra parcial retorna 201, cria um Purchase e reduz a disponibilidade exatamente pela quantidade comprada.
- Compra total retorna 201, deixa a disponibilidade zero e torna a Offer `inactive`.
- Compra acima do saldo, zero, negativa, fracionária, Offer inexistente, inativa ou esgotada não cria Purchase nem altera Offer.
- Producer e delivery não conseguem comprar; retailer não consulta vendas e producer não consulta compras.
- Dois retailers disputando o último saldo confirmam no máximo uma operação; a soma comprada não excede o total.
- Alterar preço ou nome da Offer depois da compra não altera os snapshots do Purchase.
- `GET /v1/purchases` e `GET /v1/sales` retornam somente o escopo do usuário e a paginação nativa do Laravel (`data`, `links` e `meta`).
- Uma falha no registro não deixa estoque consumido sem Purchase nem Purchase sem consumo correspondente.

## Stop condition

A feature está validada quando o contrato OpenAPI, as três requisições Bruno, os testes Red/Green e a suíte completa estiverem alinhados e passando no container PHP, sem adicionar abstrações não justificadas.
