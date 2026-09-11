# Backend Architecture & Guidelines (Laravel)

## 1. Docker Execution Environment

All PHP, Composer, Artisan, and test commands **must** be executed inside the `php` container via Docker Compose:

```bash
# Run test suite
docker compose exec php php artisan test
docker compose exec php vendor/bin/phpunit

# Run single test or filter
docker compose exec php php artisan test --filter=CreateUserActionTest
docker compose exec php vendor/bin/phpunit tests/Unit/domain/Models/User/Actions/CreateUserActionTest.php

# Run artisan / composer commands
docker compose exec php php artisan <command>
docker compose exec php composer <command>
```

Never run Laravel Pint (`vendor/bin/pint`, `composer pint`, or equivalent). PHP formatting is handled exclusively by Intelephense.

---

## 2. Project Namespaces & Structure

Namespace root: `Cultiva\` $\to$ `domain/` (configured in `composer.json` PSR-4).

```text
domain/
├── Base/                       # Shared across every entity (Contracts, Base Exceptions)
│   ├── Contracts/
│   │   └── Controller.php
│   └── Exceptions/
│       └── CultivaException.php
├── Models/                     # Feature modules grouped by entity
│   ├── User/
│   │   ├── User.php            # Eloquent model
│   │   ├── Actions/            # Business operations
│   │   ├── DTO/                # Readonly Data Transfer Objects
│   │   ├── Exceptions/         # Domain-specific exceptions
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Requests/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   └── Jobs/
├── Integrations/                # External services / APIs (no Eloquent model)
│   └── Payment/
│       ├── Adapters/
│       ├── Actions/
│       ├── DTO/
│       └── Exceptions/
└── Providers/                  # Application service providers
    ├── AppServiceProvider.php
    └── HorizonServiceProvider.php

tests/
├── Unit/                       # Mirrored source path unit tests
├── Feature/                    # Mirrored source path end-to-end / route tests
└── Fixtures/                   # Payload fixtures as PHP files (multiline strings / heredoc)
```

---

## 3. Laravel-Native APIs

Before writing manual control flow, check Laravel's native APIs and use the
method that already expresses the behavior. Prefer `firstOrFail()` or
`findOrFail()` over `first()`/`find()` followed by a manual not-found
exception. Do not duplicate framework behavior unless the application needs a
custom behavior or message.
Do not add exception renderers for standard Laravel authentication,
authorization, or not-found responses unless the application explicitly
requires a different contract.
For simple Eloquent API representations, use Laravel API Resources and
`Resource::collection()` instead of mapping models manually in controllers.
For Eloquent writes, prefer the native combined methods such as `update()`
instead of chaining `fill()` and `save()` separately.
For DTOs, rely on the constructor property types and use `??` for optional
input values; do not add redundant scalar casts or `array_key_exists()` checks.
Controllers should only coordinate HTTP input and output; move branching and
business-flow decisions into Actions.
FormRequest `validated()` already returns only fields declared in `rules()`;
fields that must be ignored should not be declared just to add redundant
`prohibited` rules. Add `prohibited` only when the request must explicitly
reject a field instead of ignoring it.

## 4. Workspace Development Skills

All development guidelines, coding standards, and testing patterns are maintained as specialized skills:

| Skill | Path | Description |
| :--- | :--- | :--- |
| **`architecture`** | [`.agents/skills/architecture/SKILL.md`](.agents/skills/architecture/SKILL.md) | **Backend Architecture & Action Pattern**: Single-responsibility Action classes, public `execute()`, DTOs/Value Objects, two-layer validation (`FormRequest` vs `Action`), DB transactions, custom domain exceptions, return contracts, design patterns inside actions (`docs/PATTERNS.md`), and anti-patterns. |
| **`tdd`** | [`.agents/skills/tdd/SKILL.md`](.agents/skills/tdd/SKILL.md) | **Test-Driven Development Standards**: Red-Green-Refactor cycle, path mirroring under `tests/Unit/` & `tests/Feature/`, Arrange-Action-Assert (AAA) structure, `$sut` convention, Mockery expectations, direct factory instantiation (`Factory::new()`), multi-route controller test splitting, and canonical array assertions. |

### Feature Test Non-Negotiables

- Não criar testes para comportamentos básicos e padrão do Laravel que não foram customizados; presume-se que o framework funcione. Testar apenas a configuração ou comportamento próprio da aplicação.
- A Feature test must execute the real route lifecycle: middleware, request, controller, Action, transformer, and database.
- Never mock the Action called by the controller. That produces a controller unit test, not a Feature test.
- Never instantiate Eloquent models with `new`, `make()`, or `setRelation()` to simulate persisted state in a Feature test.
- Persist test state with direct factories: `UserFactory::new()->create()`, including related records.
- Mock or fake only outbound boundaries that cannot run locally, such as third-party APIs or webhooks.
- Assert both the HTTP contract and relevant database side effects.

### TDD Execution Gate (Non-Negotiable)

For every new behavior, bug fix, or requirement change, the test must exist and produce a valid Red **before** any production code is written or changed:

1. Create or update the smallest relevant test, including only required test scaffolding.
2. Run that targeted test inside the `php` container.
3. Confirm it fails because the requested behavior is missing or incorrect. Infrastructure, syntax, or environment failures do not count as Red.
4. Only after that confirmed Red, change production code to reach Green.
5. Run the targeted test again, then the relevant suite, and confirm Green.

Red and Green must be separate, dependency-ordered tasks. Never combine them into one implementation task or run them in parallel. If the test passes before implementation, strengthen or correct the test until it fails for the expected reason. If a valid Red cannot be demonstrated, stop before changing production code and report the blocker.

Every implementation handoff must report the Red command and expected failure reason, followed by the Green command and result.

### API Pagination Convention (Mandatory)

- Every query returning a paginated list MUST use Laravel's native `paginate()` on Eloquent or Query Builder before fetching records.
- Read Actions MUST return `LengthAwarePaginator`. Keep it until HTTP serialization; never paginate manually with `skip()`/`take()`, array slicing, or an in-memory collection.
- Return paginators through the corresponding Laravel API Resource collection and let Laravel serialize `data`, `links`, and `meta`.
- Do not manually call `through()`, `items()`, or rebuild pagination metadata in controllers.
- Validate `page >= 1` and `1 <= per_page <= 100` in the endpoint FormRequest; defaults are 1 and 15. Invalid values return 422.
- Reuse the count performed by `paginate()` and eager-load transformed relationships; never load all records or perform another count for serialization.
- Apply this convention to all current and future paginated endpoints. No custom paginator class or middleware is required.
