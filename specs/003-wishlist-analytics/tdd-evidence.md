# TDD Evidence: Wishlist e Analytics

| Phase | Command | Result |
|---|---|---|
| US1 Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/StoreTest.php` | RED: 4 falhas porque a rota ainda não existia; esperava 201/403/422 e recebeu 404. |
| US1 Green | mesmo comando após implementação | GREEN: 4 testes, 12 assertions. |
| US2 Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/IndexTest.php tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/DestroyTest.php` | RED: listagem recebeu 405 e remoção própria recebeu 404; rota ainda não implementada. |
| US2 Green | mesmo comando após implementação | GREEN: 4 testes, 12 assertions. |
| US3 Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/AnalyticsTest.php tests/Unit/domain/Models/Wishlist/Actions/GetWishlistAnalyticsActionTest.php` | RED: 3 Feature receberam 404 e Unit não encontrou `GetWishlistAnalyticsAction`. |
| US3 Green | mesmo comando após implementação | GREEN: 4 testes, 22 assertions. |
| Regressão (registro histórico) | `docker compose exec php php artisan test tests/Feature/domain/Models/Offer tests/Feature/domain/Models/Purchase tests/Unit/domain/Integrations/ProductSource` | GREEN: 62 testes, 190 assertions. |
| Suíte completa (revalidação em 2026-09-14) | `docker compose exec php php artisan test` | GREEN: 134 testes passaram, 483 assertions, 0 falhas; exit code 0. |
| Sintaxe | `docker compose exec php sh -c '... php -l ...'` | GREEN: nenhum erro de sintaxe nos arquivos alterados. |

Laravel Pint não foi executado.

## Reconciliação da suíte completa

- O resultado anterior de 120 testes e 438 assertions foi substituído pela execução atual no workspace, incluindo as alterações locais ainda não commitadas.
- Segundo o comentário de revisão do Copilot, a descrição do PR registrava 129 testes aprovados e 3 falhas. O trecho fornecido não identifica os testes nem os erros dessas falhas; esse resultado não foi reproduzido na execução atual e não é possível atribuir uma causa sem o log original.
- Saída da execução atual: `Tests: 134 passed (483 assertions)`; `Duration: 7.16s`. Os demais registros de fases anteriores são históricos.

## Requirement change: global percentage and others

| Phase | Command | Result |
|---|---|---|
| Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/AnalyticsTest.php` | RED: `others` ausente com `limit=2`. |
| Green | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/AnalyticsTest.php tests/Unit/domain/Models/Wishlist/Actions/GetWishlistAnalyticsActionTest.php` | GREEN: 5 testes, 30 assertions. |
