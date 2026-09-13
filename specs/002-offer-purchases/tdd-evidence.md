# TDD Evidence: Compras de Ofertas

## US1 Red

- Command: `docker compose exec php php artisan test tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/StoreTest.php`
- Result: 2 failed, 2 assertions.
- Expected reason: both purchase requests returned HTTP 404 because `POST /v1/offers/{offer}/purchase` does not exist yet.
- Invalid Red attempts: `--filter=PurchaseController\\StoreTest` returned `No tests found`; the file-path command above is the valid Red command.

## US2 characterization

- Command: `docker compose exec php php artisan test tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/StoreTest.php`
- Result: 10 passed, 37 assertions after correcting the above-availability expectation to the contract's HTTP 409.
- Note: US2 introduced regression coverage for rules already implemented in the minimal US1 path (FormRequest validation, retailer middleware, Offer row lock and rollback transaction), so no separate production change was needed for this story.

## US3 Red

- Command: `docker compose exec php php artisan test tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/PurchaseIndexTest.php`
- Result: 2 failed, 2 assertions.
- Expected reason: both requests returned HTTP 404 because the purchase listing route/action had not been implemented yet.

## US4 Red

- Command: `docker compose exec php php artisan test tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/SaleIndexTest.php`
- Result: 2 failed, 2 assertions.
- Expected reason: both requests returned HTTP 404 because the sales listing route/action had not been implemented yet.

## Green results

- US1: `docker compose exec php php artisan test tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/StoreTest.php` — 10 passed, 37 assertions after US2 coverage was added.
- US3: `docker compose exec php php artisan test tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/PurchaseIndexTest.php` — 2 passed, 20 assertions.
- US4: `docker compose exec php php artisan test tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/SaleIndexTest.php` — 2 passed, 19 assertions.

## Baseline

- Command: `docker compose exec php php artisan test --filter=AvailableIndexTest`
- Result: 6 passed, 28 assertions.

## Post-implementation alignment

- Offer status Red: `StoreTest`/`UpdateTest` failed while client-controlled status was still applied; Green after status became backend-controlled and derived from stock.
- Retailer Offer details Red: `ShowTest` returned 403; Green after `GET /v1/offers/{offer}` was made available for active Offers with stock.
