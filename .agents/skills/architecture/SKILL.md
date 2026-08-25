---
name: architecture
description: Backend architecture and domain design standards for the Cultiva Laravel backend.
---

# Backend Architecture Guide (Laravel + Action Pattern)

This skill defines the architectural blueprint, domain design standards, and coding conventions for the Cultiva backend application.

---

## 1. Minimal Project Structure

Namespace root: `Cultiva\` $\to$ `domain/` (configured in `composer.json` PSR-4).

```
domain/
├── Base/                       # Shared across every entity
│   ├── Contracts/
│   │   └── Controller.php
│   └── Exceptions/
│       └── DomainException.php
├── Models/                     # Feature modules grouped by entity
│   ├── User/
│   │   ├── User.php            # Eloquent model
│   │   ├── Actions/
│   │   │   ├── CreateUserAction.php
│   │   │   └── UpdateUserAction.php
│   │   ├── DTO/
│   │   │   └── UserDTO.php
│   │   ├── Exceptions/
│   │   │   └── UserAlreadyExistsException.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── UserController.php
│   │   │   └── Requests/
│   │   │       └── StoreUserRequest.php
│   │   ├── Events/
│   │   │   └── UserCreated.php
│   │   ├── Listeners/
│   │   └── Jobs/
│   └── Producer/
│       ├── Producer.php
│       └── Actions/
│           └── RegisterProducerAction.php
├── Integrations/                # External services/APIs with no Eloquent model
│   └── Payment/
│       ├── Adapters/
│       │   └── StripeAdapter.php
│       ├── Actions/
│       │   └── ProcessPaymentAction.php
│       ├── DTO/
│       │   └── ChargeDTO.php
│       └── Exceptions/
│           └── PaymentDeclinedException.php
└── Providers/                  # Application service providers
    ├── AppServiceProvider.php
    └── HorizonServiceProvider.php
```

### Module Boundary Rules
- **Entity as Feature Module**: Everything related to one domain entity lives under `domain/Models/<Entity>/` (Model, Actions, DTOs, Exceptions, Http Controllers & Requests, Events, Listeners, Jobs).
- **Shared Base**: Only logic and contracts that are genuinely shared across multiple entities live in `domain/Base/`.
- **Integrations**: Third-party APIs, gateways, or external clients with no Eloquent model live in `domain/Integrations/<Service>/` (with an `Adapters/` subfolder).
- **No Unnecessary Abstractions**: Do **not** create `UserService`, `UserRepository`, or generic `BaseService` classes. The Action **is** the service; Eloquent **is** the repository.

---

## 2. Foundation: Action Pattern (Non-Negotiable)

All business logic lives exclusively in **Actions**.

### Action Rules Table

| Aspect | Rule |
| :--- | :--- |
| **Public Interface** | Only `execute()` is public |
| **Helper Methods** | `private` or `protected` |
| **Naming** | `<Verb><Noun>Action` (e.g., `CreateUserAction`, `ProcessPaymentAction`) |
| **Responsibility** | One action = one discrete business operation |
| **Dependency Injection** | Injected via `__construct()` |
| **Usage** | Direct instantiation `new Action(...)->execute($dto)` or container DI `app(Action::class)->execute($dto)` |
| **Return Value** | Strongly typed: Entity, `Collection`, `LengthAwarePaginator`, or dedicated result DTO (never generic `array`) |
| **Where to Use** | Controllers, Jobs, Commands, Event Listeners |
| **Stateless** | Each call is completely independent |
| **Testability** | Test `execute()` directly with DTOs and mocks |

### Standard Action Structure

```php
namespace Cultiva\Models\User\Actions;

use Cultiva\Integrations\Notification\Contracts\NotificationService;
use Cultiva\Models\User\DTO\UserDTO;
use Cultiva\Models\User\Events\UserCreated;
use Cultiva\Models\User\Exceptions\UserAlreadyExistsException;
use Cultiva\Models\User\User;

class CreateUserAction
{
    public function __construct(
        private NotificationService $notifications
    ) {}

    public function execute(UserDTO $data): User
    {
        $this->validateBusinessRules($data);

        $user = User::create((array) $data);
        $this->sendWelcomeEmail($user);
        event(new UserCreated($user));

        return $user;
    }

    private function validateBusinessRules(UserDTO $data): void
    {
        if (User::where('email', $data->email)->exists()) {
            throw new UserAlreadyExistsException('Email already registered');
        }
    }

    private function sendWelcomeEmail(User $user): void
    {
        $this->notifications->sendWelcome($user->email);
    }
}
```

---

## 3. DTOs / Value Objects

- **Location**: `domain/Models/<Entity>/DTO/<Noun>DTO.php`
- **Definition**: Readonly class with typed properties and a named constructor `from()`.
- **Purpose**: Decouples the domain layer from HTTP requests. The Controller constructs the DTO from validated request data; Actions never touch `$request`.

```php
namespace Cultiva\Models\User\DTO;

use Illuminate\Support\Facades\Hash;

readonly class UserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: Hash::make($data['password']),
        );
    }
}
```

---

## 4. Two-Layer Validation Architecture

| Layer | Class | Responsibility |
| :--- | :--- | :--- |
| **HTTP Layer** | `FormRequest` (`rules()`) | Input format validation (types, required fields, string lengths, regex). Runs before the Action. |
| **Domain Layer** | `Action` (`validateBusinessRules()`) | Domain invariant validation (uniqueness across domain entities, state checks, cross-field domain rules). |

### FormRequest Example
```php
namespace Cultiva\Models\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ];
    }
}
```

### Action Business Rule Validation Example
```php
private function validateBusinessRules(UserDTO $data): void
{
    if (User::where('email', $data->email)->exists()) {
        throw new UserAlreadyExistsException('Email already in use.');
    }
}
```

> [!IMPORTANT]
> A `FormRequest` validates HTTP format and basic input constraints. Anything that requires database queries across domain entities, entity state checks, or domain rules belongs inside the Action.

---

## 5. Database Transactions

**Mandatory Rule**: Any Action writing to 2 or more database tables **must** wrap its operations in `DB::transaction()`.

```php
namespace Cultiva\Models\Order\Actions;

use Cultiva\Models\Order\DTO\OrderDTO;
use Cultiva\Models\Order\Events\OrderCreated;
use Cultiva\Models\Order\Order;
use Cultiva\Models\Order\OrderItem;
use Cultiva\Models\Product\Contracts\InventoryService;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function __construct(
        private InventoryService $inventory
    ) {}

    public function execute(OrderDTO $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create((array) $data);

            foreach ($data->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ]);

                $this->inventory->decreaseStock(
                    $item['product_id'],
                    $item['quantity']
                );
            }

            event(new OrderCreated($order));

            return $order;
        });
    }
}
```

---

## 6. Custom Domain Exceptions

- **Location**: `domain/Models/<Entity>/Exceptions/<Domain><Condition>Exception.php`
- **Rule**: All domain exceptions extend `Cultiva\Base\Exceptions\DomainException`. Never use generic `Exception` or `AppException`.

```php
// domain/Base/Exceptions/DomainException.php
namespace Cultiva\Base\Exceptions;

use Exception;

abstract class DomainException extends Exception {}

// domain/Models/User/Exceptions/UserAlreadyExistsException.php
namespace Cultiva\Models\User\Exceptions;

use Cultiva\Base\Exceptions\DomainException;

class UserAlreadyExistsException extends DomainException {}
```

---

## 7. Return Contracts (Read & Write Actions)

Always explicitly type the return value of `execute()`. Never return a raw, untyped `array`.

```php
// Single entity return
public function execute(int $id): User
{
    return User::findOrFail($id);
}

// Collection return
public function execute(): Collection
{
    return User::where('is_active', true)->get();
}

// Paginator return
public function execute(int $perPage = 15): LengthAwarePaginator
{
    return User::paginate($perPage);
}

// Dedicated typed result object when additional metadata is needed
public function execute(string $query): SearchResult
{
    return new SearchResult(
        users: User::search($query)->get(),
        total: User::search($query)->count(),
    );
}
```

---

## 8. Integration in Controllers & Jobs

### Usage in Controller
Controllers inject the Action via method injection (or constructor), map `$request->validated()` into the DTO, and invoke `execute()`.

```php
namespace Cultiva\Models\User\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\User\Actions\CreateUserAction;
use Cultiva\Models\User\DTO\UserDTO;
use Cultiva\Models\User\Http\Requests\StoreUserRequest;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function store(
        StoreUserRequest $request,
        CreateUserAction $action
    ): JsonResponse {
        $dto = UserDTO::from($request->validated());
        $user = $action->execute($dto);

        return response()->json(['data' => $user], 201);
    }
}
```

### Usage in Queued Job
Jobs inject the Action directly into `handle()`.

```php
namespace Cultiva\Models\Order\Jobs;

use Cultiva\Models\Order\Actions\ProcessPaymentAction;
use Cultiva\Models\Order\Order;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    public function __construct(public Order $order) {}

    public function handle(ProcessPaymentAction $action): void
    {
        $action->execute($this->order);
    }

    public function failed(Exception $e): void
    {
        Log::error('Payment processing failed', ['order_id' => $this->order->id]);
    }
}
```

---

## 9. When to Extract Service, Repository, or Facade

Do not prematurely extract abstractions. Follow concrete extraction signals:

| Extract To | When |
| :--- | :--- |
| **Facade** | The exact same coordination logic appears in **3+ different Actions**, or a single Action orchestrates **4+ dependencies**. |
| **Service** | Logic is used outside Actions (Console commands, Jobs, Listeners directly), or is stateful (persistent connection, caching). |
| **Repository** | Data is fetched from **2+ distinct sources** (e.g. Database + Cache + Third-Party API), or an extremely complex query is reused across multiple Actions. |

### Verification Before Extraction
```bash
# Grep before extracting to verify 3+ actual occurrences:
grep -r "PaymentFacade" domain/ | wc -l
```

---

## 10. Design Patterns Inside Actions

Design patterns are tools used **inside** Actions when criteria match, not architectural replacements for Actions.

1. **Strategy Pattern**: Multiple interchangeable algorithms (e.g., pricing calculations, discount strategies).
2. **Factory Pattern**: Creating different implementations based on configuration or runtime input (e.g., payment provider gateways).
3. **Adapter Pattern**: Wrapping third-party APIs with incompatible interfaces (placed in `domain/Integrations/<Service>/Adapters/`).
4. **Observer / Event Pattern**: Decoupling side effects (emails, notifications, analytics) using `event(new UserCreated($user))`.
5. **Facade Pattern**: Coordinating multiple complex services when reused across 3+ actions.
6. **Template Method Pattern**: Handling variations of a fixed multi-step business process.

*(Refer to `docs/PATTERNS.md` for full design pattern catalog and detailed examples).*

---

## 11. Anti-Patterns to Avoid

- ❌ `private function execute()` (Must always be public).
- ❌ Multiple public methods on an Action (Only `execute()` is public).
- ❌ Generic services for simple CRUD operations (The Action IS the service).
- ❌ Repositories wrapping single Eloquent calls (Eloquent already provides active record).
- ❌ Over-abstraction and premature interfaces before 3+ distinct implementations exist.
- ❌ Stateful Actions (Actions must be stateless and idempotent where appropriate).
- ❌ Generic naming: `Helper`, `Utils`, `process()`, `handle()`.
- ❌ Actions returning generic, untyped `array` instead of a typed entity/DTO.
- ❌ Actions accepting `$request` directly instead of a typed DTO.
