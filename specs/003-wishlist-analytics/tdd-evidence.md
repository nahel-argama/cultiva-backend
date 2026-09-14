# TDD Evidence: Wishlist e Analytics

| Phase | Command | Result |
|---|---|---|
| US1 Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/StoreTest.php` | RED: 4 falhas porque a rota ainda não existia; esperava 201/403/422 e recebeu 404. |
| US1 Green | mesmo comando após implementação | GREEN: 4 testes, 12 assertions. |
| US2 Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/IndexTest.php tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/DestroyTest.php` | RED: listagem recebeu 405 e remoção própria recebeu 404; rota ainda não implementada. |
| US2 Green | mesmo comando após implementação | GREEN: 4 testes, 12 assertions. |
| US3 Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/AnalyticsTest.php tests/Unit/domain/Models/Wishlist/Actions/GetWishlistAnalyticsActionTest.php` | RED: 3 Feature receberam 404 e Unit não encontrou `GetWishlistAnalyticsAction`. |
| US3 Green | mesmo comando após implementação | GREEN: 4 testes, 22 assertions. |
| Regressão | `docker compose exec php php artisan test tests/Feature/domain/Models/Offer tests/Feature/domain/Models/Purchase tests/Unit/domain/Integrations/ProductSource` | GREEN: 62 testes, 190 assertions. |
| Suíte completa | `docker compose exec php php artisan test` | GREEN: 120 testes, 438 assertions. |
| Sintaxe | `docker compose exec php sh -c '... php -l ...'` | GREEN: nenhum erro de sintaxe nos arquivos alterados. |

Laravel Pint não foi executado.

## Requirement change: global percentage and others

| Phase | Command | Result |
|---|---|---|
| Red | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/AnalyticsTest.php` | RED: `others` ausente com `limit=2`. |
| Green | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/AnalyticsTest.php tests/Unit/domain/Models/Wishlist/Actions/GetWishlistAnalyticsActionTest.php` | GREEN: 5 testes, 30 assertions. |
