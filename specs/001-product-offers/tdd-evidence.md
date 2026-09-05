# TDD Evidence: Ofertas de Produtos

| Task | Docker command | Expected/observed Red | Green result |
|---|---|---|---|
| T004–T006 | `docker compose exec php php artisan test tests/Unit/domain/Base/Exceptions/CultivaExceptionTest.php` | RED confirmado: `HttpException::$statusCode` não inicializado (1 failed, 0 assertions) | GREEN: 1 passed, 1 assertion |
| T007–T010 | `docker compose exec php php artisan test --filter=GetProductActionTest` | RED confirmado: `GetProductAction` inexistente (7 failed, 0 assertions) | GREEN confirmado com `docker compose exec php vendor/bin/phpunit tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php`: 7 passed, 11 assertions |
| T011–T020 | `docker compose exec php php artisan test tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/StoreTest.php` | RED confirmado após habilitar PostGIS no `app_testing`: `CategoryFactory` inexistente e rota retorna 404 (18 failed) | GREEN: 18 passed/57 assertions; regressão US1: 25 passed/68 assertions |
| T021–T024 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php` | RED confirmado: rota retorna 404 (4 failures, 4 assertions) | GREEN: 4 passed, 6 assertions |
| T025–T032 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/IndexTest.php tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/ShowTest.php` | RED confirmado após fortalecer 404: index 405 e show sem mensagem de domínio (13 failures, 15 assertions) | GREEN: 13 passed/25 assertions; regressão Producer: 31 passed/82 assertions |
| T033–T039 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/UpdateTest.php` | RED confirmado: PATCH retorna 405 (18 failures, 18 assertions) | GREEN: 18 passed/55 assertions; regressão Producer: 49 passed/137 assertions |
| T040–T044 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Offer/Http/Controllers/AvailableOfferControllerTest.php` | RED confirmado: rota retorna 404 (7 failures, 7 assertions) | GREEN: 7 passed, 18 assertions |

Infrastructure, syntax, or database connectivity failures are not valid Red evidence.

Focused feature regression: `docker compose exec php vendor/bin/phpunit tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php tests/Feature/domain/Models/Offer/Http/Controllers` — GREEN, 67 tests and 172 assertions.

Full backend suite: `docker compose exec php vendor/bin/phpunit` — GREEN, 95 tests and 236 assertions. Redis was started for the existing throttle tests; two empty PHPUnit placeholder files were removed because they contained no tests and caused runner warnings.

Collection validation: Postman JSON parsed successfully with PHP and contains the six Offers requests. Bruno OpenCollection files follow the existing collection structure; both collections have matching methods, URLs, bearer profiles, headers, pagination parameters and JSON payloads. Token variables are empty and no credential-shaped value was found.

Architecture review: the feature uses Eloquent directly in Actions, exposes only `execute()` as each Action operation, keeps outbound HTTP inside `Integrations/ProductSource`, and introduces no repository, single-implementation interface or additional dependency.

Final gate: all changed PHP files passed `php -l`; the testing database reports the offers migration as applied; `route:list -v` confirms all six endpoints with Sanctum, `access` ability and the expected Producer/Retailer profile middleware. The final full suite remains GREEN with 95 tests and 236 assertions.
