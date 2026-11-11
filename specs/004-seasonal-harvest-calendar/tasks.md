# Tasks: Calendário de Previsão de Colheita

**Input**: Design documents from `specs/004-seasonal-harvest-calendar/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/openapi.yaml`

**Tests**: Required by backend `AGENTS.md`; every behavior follows Red-Green-Refactor in Docker.

**Organization**: Tasks grouped by user story for independent implementation and validation.

## Phase 1: Setup

**Purpose**: No project initialization needed; backend already has Laravel, Docker, tests and module structure.

## Phase 2: User Story 1 - Publicar previsão de colheita (Priority: P1) 🎯 MVP

**Goal**: Producer creates independent, producer-confirmed forecast for one product/date interval and quantity, without changing stock.

**Independent Test**: Feature test creates a manual-date forecast and a confirmed seasonal-period forecast; both persist with correct producer/product data while Offer rows stay unchanged.

### Tests for User Story 1

- [X] T001 [US1] Add producer forecast create and season reference Feature tests in `tests/Feature/domain/Models/HarvestForecast/Controllers/HarvestForecastController/StoreTest.php` and `SeasonalPeriodsTest.php`
- [X] T002 [US1] Run Red tests with `docker compose exec php php artisan test --filter=HarvestForecastController` and confirm failure because forecast routes/behavior do not exist

### Implementation for User Story 1

- [X] T003 [US1] Create forecast persistence migration in `database/migrations/2026_11_10_000000_create_harvest_forecasts_table.php`
- [X] T004 [US1] Add HarvestForecast model and producer relationship in `domain/Models/HarvestForecast/HarvestForecast.php` and `domain/Models/Producer/Producer.php`
- [X] T005 [US1] Add forecast factory in `database/factories/HarvestForecastFactory.php`
- [X] T006 [US1] Add configurable generic season references in `config/harvest_seasons.php` (catalog starts empty pending curation)
- [X] T007 [US1] Add create and seasonal-reference Actions/Requests/Resources/controller in `domain/Models/HarvestForecast/`
- [X] T008 [US1] Add producer create and season-reference routes in `routes/api/harvest-forecasts.php` and register them in `routes/api.php`
- [X] T009 [US1] Run Green tests with `docker compose exec php php artisan test --filter=HarvestForecastController` and verify no Offer/stock mutation

---

## Phase 3: User Story 2 - Consultar previsões futuras (Priority: P1)

**Goal**: Buyers and producers browse future forecasts, filter by product and overlapping date interval, and see producer/state context.

**Independent Test**: Feature test retrieves matching future rows with product and date filters and paginates; expired and nonmatching rows are excluded.

### Tests for User Story 2

- [X] T010 [US2] Add listing, filters, producer/state output and pagination Feature tests in `tests/Feature/domain/Models/HarvestForecast/Controllers/HarvestForecastController/IndexTest.php`
- [X] T011 [US2] Run Red test with `docker compose exec php php artisan test --filter=HarvestForecastControllerIndexTest` and confirm listing/filter behavior is missing

### Implementation for User Story 2

- [X] T012 [US2] Add list request and typed filter DTO in `domain/Models/HarvestForecast/Requests/ListHarvestForecastsRequest.php` and `domain/Models/HarvestForecast/DTO/ListHarvestForecastsDTO.php`
- [X] T013 [US2] Add paginated listing Action and Resource eager-loading producer company/address in `domain/Models/HarvestForecast/Actions/ListHarvestForecastsAction.php` and `domain/Models/HarvestForecast/Resources/HarvestForecastResource.php`
- [X] T014 [US2] Add buyer/producer list route with profile middleware in `routes/api/harvest-forecasts.php`
- [X] T015 [US2] Run Green tests with `docker compose exec php php artisan test --filter=HarvestForecastControllerIndexTest`

---

## Phase 4: User Story 3 - Atualizar e expirar previsões (Priority: P2)

**Goal**: Producers edit/remove their forecasts; buyers no longer see forecasts after the end date.

**Independent Test**: Feature tests verify own-record edits/removals, ownership denial, and end-date expiration.

### Tests for User Story 3

- [X] T016 [US3] Add update, delete, ownership and expiry Feature tests in `tests/Feature/domain/Models/HarvestForecast/Controllers/HarvestForecastController/UpdateTest.php` and `DeleteTest.php`
- [X] T017 [US3] Run Red tests with `docker compose exec php php artisan test --filter=HarvestForecastController` and confirm update/delete/expiry behavior is missing

### Implementation for User Story 3

- [X] T018 [US3] Add update DTO and FormRequest, preserving existing values for omitted fields in `domain/Models/HarvestForecast/DTO/UpdateHarvestForecastDTO.php` and `domain/Models/HarvestForecast/Requests/UpdateHarvestForecastRequest.php`
- [X] T019 [US3] Add update and delete Actions enforcing forecast ownership in `domain/Models/HarvestForecast/Actions/UpdateHarvestForecastAction.php` and `DeleteHarvestForecastAction.php`
- [X] T020 [US3] Add producer update/delete routes and controller coordination in `routes/api/harvest-forecasts.php` and `domain/Models/HarvestForecast/Controllers/HarvestForecastController.php`
- [X] T021 [US3] Run Green tests with `docker compose exec php php artisan test --filter=HarvestForecastController`

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Verify backend contract, database safety, and acceptance scenarios.

- [X] T022 [P] Review API output and error responses against `specs/004-seasonal-harvest-calendar/contracts/openapi.yaml`
- [X] T023 Run all backend tests with `docker compose exec php php artisan test`
- [X] T024 Run quickstart validation with Feature tests and confirm the database safety test resolves the isolated `cultiva_test`, never development database `app`
- [X] T025 Prepare frontend handoff with endpoint contracts, season suggestion behavior, filters, producer/state fields, and non-goals in `specs/004-seasonal-harvest-calendar/frontend-handoff.md`

## Dependencies & Execution Order

### Phase Dependencies

- Setup has no work; project already initialized.
- User Story 1 introduces forecast persistence and create/season behavior.
- User Story 2 depends on the entity and records from US1.
- User Story 3 depends on the entity from US1 and shared query behavior from US2.
- Polish depends on all three user stories.

### User Story Dependencies

- **US1 (P1)**: Standalone after setup; establishes forecast records.
- **US2 (P1)**: Depends on US1 persistence; independently validates buyer access and lookup.
- **US3 (P2)**: Depends on US1 persistence and US2 calendar visibility rules.

### Within Each User Story

- Write the Feature tests first and confirm Red in Docker before production changes.
- Implement only the failing story behavior, then confirm Green before starting the next story.
- Keep Product Source mocking only at the outbound API boundary; Feature tests execute real routes, Actions, Resources and test database.

## Parallel Opportunities

- No implementation tasks are marked parallel: shared route/controller/model files make parallel edits unsafe.

## Implementation Strategy

Deliver US1 create/publish and seasonal suggestion support first, then buyer listing (US2), then update/delete/expiry (US3). Frontend is a later handoff and is not part of these tasks.

## Notes

- [USn] labels map directly to user stories in `spec.md`.
- Feature tests must use `cultiva_test`, never development database `app`.
- Favorite visuals and notifications are excluded from backend implementation.
