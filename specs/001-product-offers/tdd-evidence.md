# TDD Evidence: Ofertas de Produtos

| Task | Docker command | Expected/observed Red | Green result |
|---|---|---|---|
| T004–T006 | `docker compose exec php php artisan test tests/Unit/domain/Base/Exceptions/CultivaExceptionTest.php` | RED confirmado: `HttpException::$statusCode` não inicializado (1 failed, 0 assertions) | GREEN: 1 passed, 1 assertion |
| T007–T010 | `docker compose exec php php artisan test --filter=GetProductActionTest` | RED confirmado: `GetProductAction` inexistente (7 failed, 0 assertions) | GREEN confirmado com `docker compose exec php vendor/bin/phpunit tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php`: 7 passed, 11 assertions |
| T011–T020 | `docker compose exec php php artisan test tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/StoreTest.php` | RED confirmado após habilitar PostGIS no banco configurado para testes: `CategoryFactory` inexistente e rota retorna 404 (18 failed) | GREEN: 18 passed/57 assertions; regressão US1: 25 passed/68 assertions |
| T021–T024 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php` | RED confirmado: rota retorna 404 (4 failures, 4 assertions) | GREEN: 4 passed, 6 assertions |
| T025–T032 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/IndexTest.php tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/ShowTest.php` | RED confirmado após fortalecer 404: index 405 e show sem mensagem de domínio (13 failures, 15 assertions) | GREEN: 13 passed/25 assertions; regressão Producer: 31 passed/82 assertions |
| T033–T039 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/UpdateTest.php` | RED confirmado: PATCH retorna 405 (18 failures, 18 assertions) | GREEN: 18 passed/55 assertions; regressão Producer: 49 passed/137 assertions |
| T040–T044 | `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/AvailableIndexTest.php` | RED confirmado: rota retorna 404 (7 failures, 7 assertions) | GREEN: 7 passed, 18 assertions |

Infrastructure, syntax, or database connectivity failures are not valid Red evidence.

Focused feature regression: `docker compose exec php vendor/bin/phpunit tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php tests/Feature/domain/Models/Offer/Http/Controllers` — GREEN, 67 tests and 172 assertions.

Full backend suite: `docker compose exec php vendor/bin/phpunit` — GREEN, 95 tests and 236 assertions. Redis was started for the existing throttle tests; two empty PHPUnit placeholder files were removed because they contained no tests and caused runner warnings.

Collection validation: Postman JSON parsed successfully with PHP and contains the six Offers requests. Bruno OpenCollection files follow the existing collection structure; both collections have matching methods, URLs, bearer profiles, headers, pagination parameters and JSON payloads. Token variables are empty and no credential-shaped value was found.

Architecture review: the feature uses Eloquent directly in Actions, exposes only `execute()` as each Action operation, keeps outbound HTTP inside `Integrations/ProductSource`, and introduces no repository, single-implementation interface or additional dependency.

Final gate: all changed PHP files passed `php -l`; the testing database reports the offers migration as applied; `route:list -v` confirms all six endpoints with Sanctum, `access` ability and the expected Producer/Retailer profile middleware. The final full suite remains GREEN with 95 tests and 236 assertions.

Environment consolidation: the dedicated testing environment file was removed. PHPUnit sets `APP_ENV=testing`, Laravel reuses the local `.env`, `config:show database --env=testing` resolves PostgreSQL to disposable `app`, and the full suite remains GREEN with 95 tests and 236 assertions.

An intermediate own-offers route rename (now superseded) produced the expected Red with 7 failures returning 404. After changing the route and contracts, the same command was GREEN with 7 tests and 15 assertions.

Unified offer routes: after changing only the Producer, Retailer and Category feature tests to the prefix-free URLs, the focused run produced the expected Red with 58 failures returning 404. After updating routing and dispatching the shared list by authenticated profile, the same command was GREEN with 58 tests and 159 assertions.

HTTP authentication messages: `docker compose exec php vendor/bin/phpunit tests/Feature/bootstrap/AppTest.php` produced the expected Red with 3 failures containing Laravel's default 401/403 messages. After adding explicit global JSON renderers, the same command was GREEN with 3 tests and 6 assertions, without accessing the database.

English response standardization: after changing only the HTTP expectations and adding `TranslationsTest.php`, the isolated run produced the expected Red with 4 failures because the custom catalogs existed only in Portuguese. After moving all 13 custom messages to `lang/en` and restoring `APP_LOCALE=en`, the same run was GREEN with 4 tests and 19 assertions.

Native pagination revision: `docker compose exec php vendor/bin/phpunit tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/IndexTest.php tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/AvailableIndexTest.php` produced valid RED with 13 tests, 24 assertions and 3 failures because `current_page` was missing at the response root. After returning Laravel's paginator directly via `through()` and `withQueryString()`, the same command was GREEN with 13 tests and 50 assertions. The superseded custom response/request classes and their unit tests were removed at the user's request.

Native pagination final gate: additional Feature scenarios cover first/intermediate/last/single/empty/beyond-last pages, preserved query parameters in navigation links, maximum page size and non-integer input. `docker compose exec php vendor/bin/phpunit` is GREEN with 106 tests and 322 assertions. PHP syntax checks and `git diff --check` passed. No static type checker is installed; Pint was not run, per AGENTS.md.

Final boolean pagination contract: the same focused command listed above produced valid RED with 21 tests, 71 assertions and 9 failures: missing boolean fields and unwanted native URL metadata. After selecting the six agreed fields and deriving navigation via `! onFirstPage()` and `hasMorePages()`, the command was GREEN with 21 tests and 148 assertions. Exact structure assertions reject links and additional metadata on both profile flows.

Final regression for boolean navigation: `docker compose exec php vendor/bin/phpunit` — GREEN, 106 tests and 370 assertions. All five changed PHP files pass `php -l`, and `git diff --check` passes. No PHP static type checker is installed; Pint remains prohibited by AGENTS.md.
