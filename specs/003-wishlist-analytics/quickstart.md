# Quickstart Validation: Wishlist e Analytics

## Prerequisites

- Docker Compose com o serviço `php` e banco configurados.
- Migrations, factories de User/Retailer/Company/Address e tokens Sanctum disponíveis.
- ProductSource fakeável pelo cliente HTTP do Laravel.
- Token de retailer e token de producer com ability `access`.

Todos os comandos PHP, Artisan, Composer e testes abaixo devem rodar no container `php`. Não executar Laravel Pint.

## Required TDD order

1. Documentar as quatro rotas no Bruno e no Postman, mantendo `product_id` público e os parâmetros de busca/paginação/analytics do contrato.
2. Criar os quatro Feature tests nos caminhos definidos no [plan.md](plan.md).
3. Executar Red antes de qualquer código de produção:

```bash
docker compose exec php php artisan test --filter=WishlistController\\StoreTest
docker compose exec php php artisan test --filter=WishlistController\\IndexTest
docker compose exec php php artisan test --filter=WishlistController\\DestroyTest
docker compose exec php php artisan test --filter=WishlistController\\AnalyticsTest
```

Red válido deve indicar rota, model ou comportamento ausente; falha de infraestrutura, sintaxe ou banco não conta.

4. Implementar somente após Red confirmado.
5. Executar Green direcionado e regressão:

```bash
docker compose exec php php artisan test --filter=WishlistController
docker compose exec php php artisan test tests/Unit/domain/Models/Wishlist
docker compose exec php php artisan test
```

## End-to-end acceptance

- `POST /v1/wishlist/items` aceita `product_id`, retorna 201 e snapshotta nome ProductSource + estado do endereço.
- Produto inexistente retorna 404; ausência de endereço retorna 422; duplicidade retorna 409; produtor não adiciona.
- `GET /v1/wishlist/items` isola retailer, pesquisa `product_name` e retorna paginação nativa.
- `DELETE /v1/wishlist/items/{wishlistItem}` não remove item de outro retailer e retorna 204 para item próprio.
- `GET /v1/wishlist/analytics` funciona para producer e retailer, filtra estado salvo, ordena por total/id e respeita `limit` 10/50.
- Com mais grupos que o `limit`, retorna `others` como resumo separado, contendo apenas `total` e `percentage`; sem grupos excedentes, não retorna `others`.
- `percentage` usa todos os WishlistItems do site como denominador, mesmo quando `limit` ou `state` restringem os resultados.
- Analytics retorna somente `state`, `total_items` e resultados; não retorna user, retailer ou endereço.
- Percentual é inteiro arredondado ao inteiro mais próximo; total zero retorna resultados vazios.
- Alterar endereço depois da inclusão não altera o `state` salvo.

## Stop condition

A feature está pronta para implementação quando contrato, modelo, pesquisa, quickstart e especificação estiverem alinhados. A implementação só estará pronta após Red/Green, testes relevantes e suíte completa verdes no container `php`.
