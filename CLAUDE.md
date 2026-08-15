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
// src/Actions/User/CreateUserAction.php
class CreateUserAction
{
    public function __construct(
        private UserRepository $users,
        private NotificationService $notifications
    ) {}

    public function execute(UserData $data): User
    {
        $this->validateBusinessRules($data);

        $user = $this->users->create($data);
        $this->sendWelcomeEmail($user);
        event(new UserCreated($user));
        return $user;
    }

    private function validateBusinessRules(UserData $data): void
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
// src/Http/Controllers/UserController.php
class UserController extends Controller
{
    public function store(
        StoreUserRequest $request,
        CreateUserAction $action
    ) {
        $data = UserData::from($request->validated());
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
// Input - src/Http/Requests/StoreUserRequest.php
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
private function validateBusinessRules(UserData $data): void
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
        private OrderRepository $orders,
        private ItemRepository $items,
        private InventoryService $inventory
    ) {}

    public function execute(OrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->orders->create($data);

            foreach ($data->items as $item) {
                $this->items->create([
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

```
src/
├── Actions/
│   ├── User/
│   │   ├── CreateUserAction.php
│   │   └── UpdateUserAction.php
│   ├── Post/
│   │   └── CreatePostAction.php
│   └── Payment/
│       └── ProcessPaymentAction.php
├── Data/
│   └── UserData.php
├── Exceptions/
│   ├── User/
│   │   └── UserAlreadyExistsException.php
│   └── DomainException.php
├── Models/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Events/
├── Listeners/
└── Jobs/

tests/
├── Unit/
│   └── Actions/
│       └── User/
│           └── CreateUserActionTest.php
└── Fixtures/
    └── UserFixture.php
```

---

## Data / Value Objects

Pasta `src/Data/`. Naming: `<Domain>Data`. Readonly class, built with a `from()` named constructor.

```php
// src/Data/UserData.php
readonly class UserData
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

Controller builds the `Data` object from validated input and hands it to the Action - the Action never touches `$request`.

---

## Custom Exceptions

Pasta `src/Exceptions/<Domain>/`. Naming: `<Domain><Condition>Exception` - never generic (`Exception`, `AppException`).

All domain exceptions extend a single project base:

```php
// src/Exceptions/DomainException.php
abstract class DomainException extends Exception {}

// src/Exceptions/User/UserAlreadyExistsException.php
class UserAlreadyExistsException extends DomainException {}
```

---

## Jobs

Inject the Action into `handle()`, same as a Controller. Let queue failure handling take over instead of catching everything manually.

```php
// src/Jobs/ProcessOrderJob.php
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
grep -r "PaymentFacade" src/ | wc -l
# 3+ files using it → extraction pays for itself
```

```php
// Facade - shared between multiple actions
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
- **Value Objects:** `<Noun>Data` → UserData, PaymentData (in `src/Data/`)
- **Exceptions:** `<Domain><Condition>Exception` → UserAlreadyExistsException, InvalidOrderException (in `src/Exceptions/<Domain>/`)

---

## Testing Convention

Pasta `tests/Unit/Actions/<Domain>/`. Naming: `<ActionName>Test`. Shared setup data goes in `tests/Fixtures/`.

```php
// tests/Unit/Actions/User/CreateUserActionTest.php
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
    public static function validData(): UserData
    {
        return UserData::from([
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
        return $this->users->findOrFail($id);
    }
}

class ListActiveUsersAction
{
    public function execute(): Collection
    {
        return $this->users->active()->get();
    }
}

class ListUsersAction
{
    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($perPage);
    }
}

// Structured result when a plain Collection isn't enough
class SearchUsersAction
{
    public function execute(string $query): SearchResult
    {
        return new SearchResult(
            users: $this->users->search($query)->get(),
            total: $this->users->search($query)->count(),
        );
    }
}
```

---

Never add complexity before you need it. Start minimal with Action + Eloquent. Extract Services only when logic is genuinely shared.

For the Golden Rule, pattern examples, and the Inside vs Outside / Decision Matrix breakdown, see **docs/PATTERNS.md**
