# Claude Guide: Backend Architecture (Laravel)

## Foundation: Action Pattern (Non-Negotiable)

All business logic lives in Actions. Follow these rules:

| Aspect                   | Rule                                                          |
| ------------------------ | ------------------------------------------------------------- |
| **Public Interface**     | Only `execute()` is public                                    |
| **Helper Methods**       | `private` or `protected`                                      |
| **Naming**               | `<Verb><Noun>Action` (CreateUserAction, ProcessPaymentAction) |
| **Responsibility**       | One action = one business operation                           |
| **Dependency Injection** | Via `__construct()`                                           |
| **Usage**                | `new Action()->execute($data)` or automatic DI                |
| **Return Value**         | Typed: entity, `Collection`, `LengthAwarePaginator`, or a dedicated result object (never generic `array`) |
| **Where to Use**         | Controllers, Jobs, Commands, Listeners                        |
| **Stateless**            | Each call is independent                                      |
| **Testability**          | Test `execute()` directly                                     |

### Standard Action Structure

```php
// domain/Models/User/Actions/CreateUserAction.php
namespace Cultiva\Models\User\Actions;

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
        $this->notifications->send($user->email, new WelcomeEmail());
    }
}
```

### Usage in Controller

```php
// domain/Models/User/Http/Controllers/UserController.php
namespace Cultiva\Models\User\Http\Controllers;

class UserController extends Controller
{
    public function store(
        StoreUserRequest $request,
        CreateUserAction $action
    ) {
        $data = UserDTO::from($request->validated());
        $user = $action->execute($data);
        return redirect()->route('users.show', $user);
    }
}
```

---

## Validation

Two layers, two jobs:

| Layer                    | Responsibility                                    |
| ------------------------ | -------------------------------------------------- |
| `FormRequest`            | Input validation (types, required, format). Runs before the Action, HTTP-layer only. |
| `Action::execute()`      | Business rule validation (uniqueness across domain rules, state checks, cross-entity checks). |

```php
// Input - domain/Models/User/Http/Requests/StoreUserRequest.php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];
    }
}

// Business rule - inside the Action (private helper)
private function validateBusinessRules(UserDTO $data): void
{
    if (User::where('email', $data->email)->exists()) {
        throw new UserAlreadyExistsException();
    }
}
```

A FormRequest rule that only checks a single field belongs in `rules()`. Anything that needs domain knowledge, other entities, or cross-field state belongs in the Action.

---

## Database Transactions

**Rule:** any Action writing to 2+ tables wraps its body in `DB::transaction()`.

```php
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
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
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

## Minimal Project Structure

**You don't need:**

- `UserService` - Action **IS** the service
- `UserRepository` - Eloquent already does that
- `BaseService` or abstraction for its own sake

Namespace root: `Cultiva\` → `domain/` (see `composer.json` psr-4).

```
domain/
├── Base/                       # shared across every entity
│   ├── Contracts/
│   │   └── Controller.php
│   └── Exceptions/
│       └── DomainException.php
├── Models/
│   ├── User/
│   │   ├── User.php            # the Eloquent model
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
│   └── Post/
│       ├── Post.php
│       └── Actions/
│           └── CreatePostAction.php
├── Integrations/                # external services/APIs - no Eloquent model
│   └── Payment/
│       ├── Adapters/
│       │   └── StripeAdapter.php
│       ├── Actions/
│       │   └── ProcessPaymentAction.php
│       ├── DTO/
│       │   └── ChargeDTO.php
│       └── Exceptions/
│           └── PaymentDeclinedException.php
└── Providers/                  # boot / service providers
    ├── AppServiceProvider.php
    └── HorizonServiceProvider.php

tests/
├── Unit/
│   └── Models/
│       └── User/
│           └── Actions/
│               └── CreateUserActionTest.php
└── Fixtures/
    └── UserFixture.php
```

Everything for one entity lives together under `domain/Models/<Entity>/` - the entity folder IS the feature module (model, Actions, DTO, Exceptions, Http, Events, Listeners, Jobs). Only what's genuinely shared across entities goes in `domain/Base/`.

`domain/Integrations/<Service>/` is for external APIs/gateways with no Eloquent model behind them (payment gateways, email providers, third-party APIs) - same internal shape as a `Models/<Entity>/` folder (Actions, DTO, Exceptions), plus an `Adapters/` folder wrapping the external client (see Adapter pattern in docs/PATTERNS.md).

---

## DTOs / Value Objects

Pasta `domain/Models/<Entity>/DTO/`. Naming: `<Domain>DTO`. Readonly class, built with a `from()` named constructor.

```php
// domain/Models/User/DTO/UserDTO.php
namespace Cultiva\Models\User\DTO;

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

Controller builds the `DTO` object from validated input and hands it to the Action - the Action never touches `$request`.

---

## Custom Exceptions

Pasta `domain/Models/<Entity>/Exceptions/`. Naming: `<Domain><Condition>Exception` - never generic (`Exception`, `AppException`).

All domain exceptions extend a single project base living in `domain/Base/`:

```php
// domain/Base/Exceptions/DomainException.php
namespace Cultiva\Base\Exceptions;

abstract class DomainException extends Exception {}

// domain/Models/User/Exceptions/UserAlreadyExistsException.php
namespace Cultiva\Models\User\Exceptions;

use Cultiva\Base\Exceptions\DomainException;

class UserAlreadyExistsException extends DomainException {}
```

---

## Jobs

Inject the Action into `handle()`, same as a Controller. Let queue failure handling take over instead of catching everything manually.

```php
// domain/Models/Order/Jobs/ProcessOrderJob.php
namespace Cultiva\Models\Order\Jobs;

class ProcessOrderJob implements ShouldQueue
{
    public function __construct(public Order $order) {}

    public function handle(ProcessPaymentAction $action): void
    {
        $action->execute($this->order);
    }

    public function failed(Exception $e): void
    {
        Log::error('Payment job failed', ['order_id' => $this->order->id]);
    }
}
```

```php
dispatch(new ProcessOrderJob($order));
dispatch(new ProcessOrderJob($order))->onQueue('payments');
```

---

## When You Actually Need Service/Repository

Don't extract on a feeling. Check against a concrete signal:

| Extract to...  | When...                                                                  |
| -------------- | ------------------------------------------------------------------------ |
| **Facade**     | Same coordination logic appears in 3+ different Actions, or one Action orchestrates 4+ dependencies |
| **Service**    | Logic is used outside Actions too (Console, Job, Listener directly), or is stateful (persistent connection, cache) |
| **Repository** | Data comes from 2+ sources (DB + cache + external API), or a complex query is reused by more than one Action |

Verify before extracting - grep for actual reuse, don't guess:

```bash
grep -r "PaymentFacade" domain/ | wc -l
# 3+ files using it → extraction pays for itself
```

```php
// Facade - shared between multiple actions - domain/Base/Facades/PaymentFacade.php
namespace Cultiva\Base\Facades;

class PaymentFacade
{
    public function process(Order $order): Payment { ... }
}

class ProcessPaymentAction
{
    public function __construct(private PaymentFacade $payments) {}
    public function execute(Order $order): Payment
    {
        return $this->payments->process($order);
    }
}
```

---

## Design Patterns (Mandatory When the Criteria Match)

**Patterns are tools INSIDE Actions, not replacements.**

Not decorative, not optional flourish - but not forced either. Each pattern below has a **When** trigger in docs/PATTERNS.md. If the situation matches the trigger, apply the pattern; don't hand-roll an if/else chain or a switch that a Strategy/Factory already solves. If it doesn't match, don't add it "just in case" - see Anti-Patterns below.

7 Available Patterns:

1. **Strategy** - Multiple algorithms (injected)
2. **Factory** - Create different implementations
3. **Adapter** - Wrap incompatible APIs
4. **Observer/Events** - Decouple systems
5. **Composite** - Hierarchical structures (see caveat in docs/PATTERNS.md - Gate/Policy covers most permission cases instead)
6. **Facade** - Coordinate services
7. **Template Method** - Variations of same process

**See docs/PATTERNS.md for detailed examples, the Golden Rule, and the Decision Matrix.**

---

## Anti-Patterns to Avoid

- `private function execute()` (must be public)
- Multiple public methods (only `execute()`)
- Service for simple operations (Action IS the service)
- Repository for every Model (Eloquent already does that)
- Over-abstraction from the start
- Not injecting dependencies
- Stateful Actions
- Generic naming: `Helper`, `Utils`, `process()`
- Actions that don't return anything
- Actions returning a generic `array` instead of a typed result

---

## Naming Convention

**Actions:**

- `<Verb><Noun>Action` → CreateUserAction, UpdatePostAction, ProcessPaymentAction

**Supporting Classes:**

- **Strategies:** `<Concept>Strategy` → DiscountStrategy, PricingStrategy
- **Factories:** `<Subject>Factory` → PaymentProviderFactory
- **Adapters:** `<Service>Adapter` → StripeAdapter
- **Events:** `<Noun><Verb>ed` → UserCreated, OrderPaid
- **Listeners:** `<Verb><Noun>Listener` → SendWelcomeEmailListener
- **DTOs:** `<Noun>DTO` → UserDTO, OrderDTO (in `domain/Models/<Entity>/DTO/`)
- **Exceptions:** `<Domain><Condition>Exception` → UserAlreadyExistsException, InvalidOrderException (in `domain/Models/<Entity>/Exceptions/`)

---

## Testing Convention

Pasta `tests/Unit/Models/<Entity>/Actions/`, mirroring `domain/Models/<Entity>/Actions/`. Naming: `<ActionName>Test`. Shared setup data goes in `tests/Fixtures/`.

```php
// tests/Unit/Models/User/Actions/CreateUserActionTest.php
class CreateUserActionTest extends TestCase
{
    #[Test]
    public function it_creates_user_with_valid_data(): void
    {
        $action = app(CreateUserAction::class);
        $user = $action->execute(UserFixture::validData());

        $this->assertDatabaseHas('users', ['email' => $user->email]);
    }

    #[Test]
    public function it_throws_exception_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $action = app(CreateUserAction::class);

        $this->expectException(UserAlreadyExistsException::class);
        $action->execute(UserFixture::validData());
    }
}
```

```php
// tests/Fixtures/UserFixture.php
class UserFixture
{
    public static function validData(): UserDTO
    {
        return UserDTO::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);
    }
}
```

---

## Return Contracts (Read Actions)

Always type the return - never a generic `array`.

```php
class GetUserAction
{
    public function execute(int $id): User
    {
        return User::findOrFail($id);
    }
}

class ListActiveUsersAction
{
    public function execute(): Collection
    {
        return User::active()->get();
    }
}

class ListUsersAction
{
    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return User::paginate($perPage);
    }
}

// Structured result when a plain Collection isn't enough
class SearchUsersAction
{
    public function execute(string $query): SearchResult
    {
        return new SearchResult(
            users: User::search($query)->get(),
            total: User::search($query)->count(),
        );
    }
}
```

---

Never add complexity before you need it. Start minimal with Action + Eloquent. Extract Services only when logic is genuinely shared.

For the Golden Rule, pattern examples, and the Inside vs Outside / Decision Matrix breakdown, see **docs/PATTERNS.md**
