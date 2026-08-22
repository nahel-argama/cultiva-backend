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

---

## 2. Project Namespaces & Structure

Namespace root: `Cultiva\` $\to$ `domain/` (configured in `composer.json` PSR-4).

```text
domain/
├── Base/                       # Shared across every entity (Contracts, Base Exceptions)
│   ├── Contracts/
│   │   └── Controller.php
│   └── Exceptions/
│       └── DomainException.php
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
└── Feature/                    # Mirrored source path end-to-end / route tests
```

---

## 3. Workspace Development Skills

All development guidelines, coding standards, and testing patterns are maintained as specialized skills:

| Skill | Path | Description |
| :--- | :--- | :--- |
| **`architecture`** | [`.agents/skills/architecture/SKILL.md`](.agents/skills/architecture/SKILL.md) | **Backend Architecture & Action Pattern**: Single-responsibility Action classes, public `execute()`, DTOs/Value Objects, two-layer validation (`FormRequest` vs `Action`), DB transactions, custom domain exceptions, return contracts, design patterns inside actions (`docs/PATTERNS.md`), and anti-patterns. |
| **`tdd`** | [`.agents/skills/tdd/SKILL.md`](.agents/skills/tdd/SKILL.md) | **Test-Driven Development Standards**: Red-Green-Refactor cycle, path mirroring under `tests/Unit/` & `tests/Feature/`, Arrange-Action-Assert (AAA) structure, `$sut` convention, Mockery expectations, direct factory instantiation (`Factory::new()`), multi-route controller test splitting, and canonical array assertions. |
