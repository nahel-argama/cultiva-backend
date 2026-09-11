---
description: "Task list for offer purchases"
---

# Tasks: Compras de Ofertas

**Input**: Design documents from `/specs/002-offer-purchases/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/openapi.yaml`, `quickstart.md`

**Execution gate**: Bruno and contract artifacts precede implementation. For every story, write and run the relevant Feature test in Red before changing production code. All PHP, Artisan, Composer and test commands run inside the `php` container; never run Pint.

## Phase 1: Contract and Bruno Preparation

**Purpose**: Freeze the public API shape and create the client requests before production implementation.

- [X] T001 [P] Confirm the three endpoint contracts, response status codes, payload, authorization profiles and pagination shape in `specs/002-offer-purchases/contracts/openapi.yaml`
- [X] T002 [P] Create the Bruno folder and request definitions for purchase, my purchases and my sales in `collection/bruno/API/Purchases/folder.yml`, `collection/bruno/API/Purchases/Buy Offer.yml`, `collection/bruno/API/Purchases/List My Purchases.yml` and `collection/bruno/API/Purchases/List My Sales.yml`
- [X] T003 Run a contract/structure review against `specs/002-offer-purchases/spec.md`, `specs/002-offer-purchases/plan.md` and the Bruno files; record any mismatch before writing production code

**Checkpoint**: OpenAPI and Bruno describe the exact routes that tests and implementation must follow.

---

## Phase 2: Foundational Test Environment

**Purpose**: Prepare only shared test support; no purchase production code may be written in this phase.

- [X] T004 [P] Confirm the existing `ProducerFactory`, `RetailerFactory`, `OfferFactory`, Sanctum ability setup and `tests/TestCase.php` support the planned Feature tests; add only minimal shared factory/test helpers if a concrete gap is found in `database/factories/` or `tests/TestCase.php`
- [X] T005 Confirm the PostgreSQL test database can run the existing Offer migrations and execute a baseline Offer feature test with `docker compose exec php php artisan test --filter=AvailableIndexTest`

**Checkpoint**: Shared test infrastructure is proven; no Purchase migration, model, route, Action or controller has been implemented.

---

## Phase 3: User Story 1 - Comprar uma oferta disponível (Priority: P1) 🎯 MVP

**Goal**: Let an authenticated retailer buy a partial or total quantity of an active Offer, persist the Purchase, update availability and deactivate the Offer when exhausted.

**Independent Test**: A retailer buys 30 of 100 units through the real route and receives one Purchase with backend-calculated snapshots while the Offer has 70 units left; buying the final units deactivates the Offer.

### Tests for User Story 1 — write and run Red first

- [X] T006 [US1] Write the real-route Feature scenarios for partial purchase, total purchase, backend price/total calculation and persisted Offer/Purchase effects in `tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/StoreTest.php`
- [X] T007 [US1] Run `docker compose exec php php artisan test --filter=PurchaseController\\StoreTest` and confirm a valid Red caused by the missing purchase behavior, not syntax, infrastructure or database failure; record the command and failure in `specs/002-offer-purchases/tdd-evidence.md`

### Implementation for User Story 1 — only after T007 Red

- [X] T008 [US1] Create the `purchases` migration with foreign keys, quantity/price constraints and snapshot fields from `specs/002-offer-purchases/data-model.md` in `database/migrations/*_create_purchases_table.php`
- [X] T009 [US1] Add the Purchase Eloquent model, casts, fillable fields and Offer/Retailer/Producer relationships in `domain/Models/Purchase/Purchase.php`, plus the reverse Offer relationship in `domain/Models/Offer/Offer.php`
- [X] T010 [US1] Add `PurchaseFactory` with realistic defaults and explicit override support in `database/factories/PurchaseFactory.php`
- [X] T011 [US1] Add the typed `CreatePurchaseDTO` and `StorePurchaseRequest` that accept only a positive integer `quantity` in `domain/Models/Purchase/DTO/CreatePurchaseDTO.php` and `domain/Models/Purchase/Http/Requests/StorePurchaseRequest.php`
- [X] T012 [US1] Implement `CreatePurchaseAction::execute()` in `domain/Models/Purchase/Actions/CreatePurchaseAction.php` using one database transaction, a row lock on the Offer, current status/availability validation, backend total calculation, Purchase creation, `reserved_quantity` increment and automatic inactive status on exhaustion
- [X] T013 [US1] Add `PurchaseResource` with the snapshot and relationship fields required by the contract in `domain/Models/Purchase/Http/Resources/PurchaseResource.php`
- [X] T014 [US1] Add `PurchaseController::store()` as a thin validated-input/DTO/Action/Resource flow in `domain/Models/Purchase/Http/Controllers/PurchaseController.php`
- [X] T015 [US1] Register `POST /v1/offers/{offer}/purchase` under Sanctum access and retailer profile middleware in `routes/api.php`
- [X] T016 [US1] Run `docker compose exec php php artisan test --filter=PurchaseController\\StoreTest`, verify Green, then run the affected Offer tests to prove existing catalog availability still follows `reserved_quantity` and status

**Checkpoint**: US1 is independently functional; partial and total purchases are atomic and the MVP can be demonstrated through Bruno.

---

## Phase 4: User Story 2 - Impedir compras inválidas e overselling (Priority: P1)

**Goal**: Reject invalid profiles, states and quantities, preserve atomicity, and prevent concurrent purchases from consuming the same stock.

**Independent Test**: Against an Offer with 10 units left, two retailers attempt to buy 10 concurrently; at most one succeeds, no more than 10 units are committed, and all invalid attempts leave the database unchanged.

**Dependency**: Depends on the US1 Purchase model and purchase route, but its tests must be written before changing the production behavior under test.

### Tests for User Story 2 — write and run Red first

- [X] T017 [US2] Extend `tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/StoreTest.php` with zero, negative, fractional and above-availability quantities, nonexistent/inactive/exhausted Offers, producer/delivery attempts, unauthenticated access and client-supplied commercial fields
- [X] T018 [US2] Add transaction rollback coverage and a concurrent purchase scenario that exercises the same Offer in `tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/StoreTest.php`
- [X] T019 [US2] Run `docker compose exec php php artisan test --filter=PurchaseController\\StoreTest`, correct the contract expectation for 409 stock exhaustion, and append the characterization result to `specs/002-offer-purchases/tdd-evidence.md`

### Implementation for User Story 2 — only after T019 Red

- [X] T020 [US2] Add localized domain messages in `lang/en/purchases.php` and reuse `CultivaException` for unavailable stock/not-found responses; no custom exception class was needed
- [X] T021 [US2] Complete `CreatePurchaseAction` validation and locked-row ordering in `domain/Models/Purchase/Actions/CreatePurchaseAction.php` so status, available quantity and all invariants are checked after the lock and before either write
- [X] T022 [US2] Add/verify database constraints and indexes needed for quantity bounds, money precision and retailer/producer purchase queries in `database/migrations/*_create_purchases_table.php`
- [X] T023 [US2] Verify profile middleware and route behavior for retailer-only purchase access in `routes/api.php` and add no bypass through direct IDs or client-supplied ownership fields
- [X] T024 [US2] Run `docker compose exec php php artisan test --filter=PurchaseController\\StoreTest` and the relevant Offer controller tests; confirm Green for invalid, rollback and concurrency scenarios

**Checkpoint**: No valid race can oversell; every rejected request leaves both Purchase count and Offer quantity unchanged.

---

## Phase 5: User Story 3 - Consultar minhas compras (Priority: P2)

**Goal**: Let a retailer list only their own Purchase history through a paginated endpoint with immutable commercial snapshots.

**Independent Test**: Create Purchases for two retailers, call `GET /v1/purchases` as one retailer, and verify only that retailer's records and the native `data`, `links` and `meta` pagination fields are returned.

### Tests for User Story 3 — write and run Red first

- [X] T025 [US3] Write real-route Feature tests for own-scope filtering, purchase snapshot output, pagination defaults/navigation/limits and retailer-only authorization in `tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/PurchaseIndexTest.php`
- [X] T026 [US3] Run `docker compose exec php php artisan test --filter=PurchaseController\\PurchaseIndexTest` and confirm a valid Red caused by the missing list endpoint/action; append Red evidence to `specs/002-offer-purchases/tdd-evidence.md`

### Implementation for User Story 3 — only after T026 Red

- [X] T027 [US3] Add `ListPurchasesRequest` with the existing `page`/`per_page` validation defaults and maximum in `domain/Models/Purchase/Http/Requests/ListPurchasesRequest.php`
- [X] T028 [US3] Implement `ListRetailerPurchasesAction` returning a `LengthAwarePaginator` with eager loading, deterministic newest-first ordering and `paginate()` filtered by authenticated retailer in `domain/Models/Purchase/Actions/ListRetailerPurchasesAction.php`
- [X] T029 [US3] Add `PurchaseController::index()` serialization using `PurchaseResource::collection($paginator)` in `domain/Models/Purchase/Http/Controllers/PurchaseController.php`
- [X] T030 [US3] Register `GET /v1/purchases` for authenticated retailers in `routes/api.php` and ensure producer/delivery access is denied consistently
- [X] T031 [US3] Run `docker compose exec php php artisan test --filter=PurchaseController\\PurchaseIndexTest` and confirm Green, including no exposure of another retailer's purchases and no extra pagination wrapper

**Checkpoint**: US3 independently powers a future “Minhas compras” screen.

---

## Phase 6: User Story 4 - Consultar minhas vendas (Priority: P2)

**Goal**: Let a producer list Purchases originating from their own Offers, paginated and isolated from other producers.

**Independent Test**: Create Purchases over Offers owned by two producers, call `GET /v1/sales` as one producer, and verify only that producer's sales are returned with the standard pagination contract.

### Tests for User Story 4 — write and run Red first

- [X] T032 [US4] Write real-route Feature tests for producer scope through Offer ownership, buyer/product/snapshot output, pagination and producer/retailer/delivery authorization in `tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/SaleIndexTest.php`
- [X] T033 [US4] Run `docker compose exec php php artisan test --filter=PurchaseController\\SaleIndexTest` and confirm a valid Red caused by the missing sales endpoint/action; append Red evidence to `specs/002-offer-purchases/tdd-evidence.md`

### Implementation for User Story 4 — only after T033 Red

- [X] T034 [US4] Implement `ListProducerSalesAction` returning a `LengthAwarePaginator` filtered through the authenticated producer's Offers, with eager loading and deterministic ordering in `domain/Models/Purchase/Actions/ListProducerSalesAction.php`
- [X] T035 [US4] Extend `PurchaseController::index()` or add the minimal producer-specific controller method to serialize sales using `PurchaseResource::collection($paginator)` in `domain/Models/Purchase/Http/Controllers/PurchaseController.php`
- [X] T036 [US4] Register `GET /v1/sales` for authenticated producers in `routes/api.php` and ensure retailers cannot use the producer sales scope
- [X] T037 [US4] Run `docker compose exec php php artisan test --filter=PurchaseController\\SaleIndexTest` and confirm Green, including producer isolation and pagination behavior

**Checkpoint**: US4 independently powers a future “Minhas vendas” screen without exposing other producers' transactions.

---

## Phase 7: Polish and Cross-Cutting Validation

**Purpose**: Prove the complete contract without expanding scope.

- [X] T038 [P] Review and retain the Bruno examples/variables in `collection/bruno/API/Purchases/`; no behavior divergence from the contract was found
- [X] T039 [P] Review `specs/002-offer-purchases/contracts/openapi.yaml`, `specs/002-offer-purchases/data-model.md` and Bruno requests against the implemented routes and response fields
- [X] T040 Run the focused suite in the container with `docker compose exec php php artisan test --filter=PurchaseController`
- [X] T041 Run the existing Offer regression suite in the container with `docker compose exec php php artisan test --filter=OfferController`
- [X] T042 Run the full test suite in the container with `docker compose exec php php artisan test`
- [X] T043 Execute the quickstart scenarios through the focused/full PHPUnit Feature suites, verify the Red/Green evidence in `specs/002-offer-purchases/tdd-evidence.md`, and note that the installed Bruno binary is GUI-only in this environment
- [X] T044 Align Offer lifecycle and detail visibility after product clarification: backend-controlled status on POST/PATCH, retailer access to available Offer details, updated OpenAPI/quickstart/Bruno/Postman artifacts, and regression coverage

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1** has no code dependency and MUST finish before tests or implementation.
- **Phase 2** depends on Phase 1 and only proves shared test infrastructure.
- **Phase 3 / US1** depends on Phase 2 and is the MVP.
- **Phase 4 / US2** depends on US1's purchase path and hardens its rules.
- **Phase 5 / US3** depends on the Purchase entity from US1; it can start after US1 and does not require US2's final test matrix.
- **Phase 6 / US4** depends on the Purchase entity and pagination/controller structure from US3.
- **Phase 7** depends on all desired stories being Green.

### User Story Dependencies

- **US1 (P1)**: Can start after Phase 2; no other story dependency; MVP.
- **US2 (P1)**: Extends US1 purchase behavior and requires its route/model.
- **US3 (P2)**: Requires the Purchase entity and factory from US1; independent of the sales endpoint.
- **US4 (P2)**: Requires the Purchase entity and shared pagination/transformer structure; independent of retailer listing behavior.

### Mandatory TDD Order Within Each Story

1. Write the smallest relevant Feature test(s).
2. Run the targeted command in the `php` container and confirm valid Red.
3. Record the Red command and reason in `tdd-evidence.md`.
4. Change production code only after Red.
5. Run the targeted command again for Green.
6. Run the relevant regression tests before moving to the next story.

## Parallel Opportunities

- T001 and T002 can run in parallel because they touch separate contract/documentation files; T003 waits for both.
- After US1 is Green, US3 test preparation (T025–T026) and US2 test preparation (T017–T019) can be prepared in parallel, but production changes must respect their own Red gates.
- After the shared Purchase model is stable, US3 and US4 list implementations can be split by files/actions; avoid parallel edits to `PurchaseController.php` and `routes/api.php`.
- T038 and T039 can run in parallel with each other after implementation; T040–T043 remain sequential validation gates.

## Implementation Strategy

### MVP First (User Story 1 only)

1. Complete Phase 1 and Phase 2.
2. Write/run US1 tests in Red.
3. Implement the Purchase path and run it Green.
4. Stop and validate partial purchase, total purchase, snapshot and Offer availability before adding list screens.

### Incremental Delivery

1. Add US2 concurrency and invalid-state protection; re-run US1 regression.
2. Add US3 retailer purchase history; validate ownership and pagination.
3. Add US4 producer sales history; validate Offer ownership and pagination.
4. Run Polish/full suite and Bruno contract validation.

## Completion Criteria

- Every task uses the required checkbox, sequential ID, optional `[P]` marker, story label where required, and concrete file path.
- Bruno and OpenAPI exist before production implementation tasks are started.
- Every story has Red and Green commands recorded.
- Partial/total purchase, rollback, authorization, concurrency, snapshots and both paginated lists are covered.
- `docker compose exec php php artisan test` is Green and no Pint command is used.
