# Quickstart: Calendário de Previsão de Colheita

Run all Laravel and PHPUnit commands through Docker Compose in the backend repository.

## Prerequisites

- Backend containers running, including `php` and PostgreSQL.
- Isolated `cultiva_test` database prepared as described in `specs/001-product-offers/quickstart.md`.
- Product Source available for create/update feature scenarios, or mocked at its outbound boundary.

## Validation scenarios

1. Create a one-day forecast as a producer; verify persisted product, quantity and producer, and verify no Offer was created or changed.
2. Configure a product reference in `config/harvest_seasons.php`, retrieve it through `GET /v1/harvest-season-periods`, and submit the selected dates; verify dates are stored only after producer confirmation.
3. Create a manual forecast for a product with no seasonal reference.
4. Query as a buyer using both product and date filters; verify only overlapping future forecasts appear, with producer name and state.
5. Attempt update and delete as a different producer; verify access is denied and persisted values remain unchanged.
6. Query a forecast whose end date has passed; verify it no longer appears.
7. Submit zero quantity, missing product, past date, or inverted date interval; verify validation/domain rejection and no partial write.

## Commands

```bash
docker compose exec php php artisan test --filter=HarvestForecast
```

Tests must run against `cultiva_test`, never development database `app`.
