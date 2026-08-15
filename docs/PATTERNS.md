# Design Patterns Reference (Laravel + Action Pattern)

**See CLAUDE.md for Action Pattern foundation and quick start.**

This document details each design pattern with examples and decision guidelines.

---

## 1. Strategy Pattern

**When:** Multiple algorithms for the same task within an Action.

**Inside Actions:**

- Discount calculations (percentage, fixed, tiered)
- Pricing strategies (different rules per customer type)
- Validation rules (different per entity)
- Notification channels (email, SMS, push)

**Naming:** `<Concept>Strategy`

```php
interface PricingStrategy
{
    public function calculate(float $basePrice, int $quantity): float;
}

class PercentageDiscountStrategy implements PricingStrategy
{
    public function __construct(private float $discountPercent) {}

    public function calculate(float $basePrice, int $quantity): float
    {
        return $basePrice * $quantity * (1 - $this->discountPercent / 100);
    }
}

class TieredPricingStrategy implements PricingStrategy
{
    public function calculate(float $basePrice, int $quantity): float
    {
        if ($quantity <= 10) return $basePrice * $quantity;
        if ($quantity <= 100) return $basePrice * $quantity * 0.95;
        return $basePrice * $quantity * 0.9;
    }
}

// Strategy INSIDE Action
class CreateOrderAction
{
    public function __construct(private PricingStrategy $pricingStrategy) {}

    public function execute(OrderData $data): Order
    {
        $total = $this->pricingStrategy->calculate($data->basePrice, $data->quantity);

        $order = Order::create([
            'total' => $total,
            'quantity' => $data->quantity,
        ]);

        return $order;
    }
}
```

---

## 2. Factory Pattern

**When:** Action needs to create different implementations based on type/config.

**Inside Actions:**

- Payment providers (Stripe, PayPal, Square)
- Storage drivers (S3, local, Google Cloud)
- Notification services (Mail, SMS, Slack)
- Report generators, data importers

**Naming:** `<Subject>Factory`

```php
interface PaymentProvider
{
    public function process(Payment $payment): PaymentResult;
}

class PaymentProviderFactory
{
    public static function create(string $provider): PaymentProvider
    {
        return match($provider) {
            'stripe' => new StripeProvider(),
            'paypal' => new PayPalProvider(),
            default => throw new InvalidProviderException()
        };
    }
}

// Factory INSIDE Action
class ProcessPaymentAction
{
    public function __construct(private PaymentProviderFactory $factory) {}

    public function execute(Payment $payment): PaymentResult
    {
        $provider = $this->factory->create(config('payments.default'));
        $result = $provider->process($payment);

        $payment->update(['status' => $result->success ? 'paid' : 'failed']);
        event(new PaymentProcessed($payment, $result));

        return $result;
    }
}
```

---

## 3. Adapter Pattern

**When:** Action needs to integrate third-party APIs or legacy code with incompatible interfaces.

**Inside Actions:**

- Wrapping payment gateway APIs
- Old database models → new repository interface
- External services → your app interface

**Naming:** `<Service>Adapter` or `<Legacy><New>Adapter`

```php
// Third-party API with old interface
class OldPaymentGateway
{
    public function processTransaction($amount, $token) { ... }
}

// Adapter to modern interface
class OldPaymentGatewayAdapter implements PaymentProvider
{
    public function __construct(private OldPaymentGateway $gateway) {}

    public function process(Payment $payment): PaymentResult
    {
        $result = $this->gateway->processTransaction(
            $payment->amount,
            $payment->token
        );

        return new PaymentResult(
            success: $result['success'],
            transactionId: $result['id']
        );
    }
}

// Adapter INSIDE Action
class ProcessPaymentAction
{
    public function __construct(
        private PaymentProviderFactory $factory,
        private OldPaymentGatewayAdapter $legacyAdapter
    ) {}

    public function execute(Payment $payment): PaymentResult
    {
        $result = $this->legacyAdapter->process($payment);
        $payment->update(['status' => $result->success ? 'paid' : 'failed']);

        return $result;
    }
}
```

---

## 4. Observer/Events (Laravel Native)

**When:** Action completes and multiple systems need to react independently.

**Inside Actions:**

- User registered → send welcome email + log audit + notify analytics
- Order created → check inventory + process payment + update stock
- Payment completed → email customer + mark order as paid

**Use:** Laravel's built-in `event()` and `Event::listen()`

```php
// Action fires event at the end
class CreateUserAction
{
    public function __construct(private UserRepository $users) {}

    public function execute(UserData $data): User
    {
        $user = $this->users->create($data);

        // Dispatch event for other systems to listen
        event(new UserCreated($user));

        return $user;
    }
}

// Independent listeners react to event
class SendWelcomeEmailListener
{
    public function handle(UserCreated $event): void
    {
        Mail::to($event->user->email)->send(new WelcomeEmail());
    }
}

class LogUserAuditListener
{
    public function handle(UserCreated $event): void
    {
        Log::info('User created', ['user_id' => $event->user->id]);
    }
}

// Register in EventServiceProvider
protected $listen = [
    UserCreated::class => [
        SendWelcomeEmailListener::class,
        LogUserAuditListener::class,
        NotifyAnalyticsListener::class,
    ],
];
```

---

## 5. Composite Pattern

**When to avoid:** don't reach for Composite for plain permission checks - use Laravel's native Gate/Policy instead:

```php
// Gate - not Composite
Gate::define('update-post', function (User $user, Post $post) {
    return $user->id === $post->user_id;
});
```

Gate/Policy covers most authorization needs. Composite only earns its place when the structure is **genuinely recursive**:

- Menu/category trees (Category > Subcategory > ...)
- Role hierarchies with real inheritance (Role > Permission > Action)
- Composed pricing rules (Base + Discount + Tax, each wrapping the next)

**Inside Actions:**

- Role hierarchies in access control (only when inheritance is real, not a flat list)
- Category navigation

**Example:**

```php
interface PermissionComponent
{
    public function hasPermission(string $permission): bool;
}

class UserPermission implements PermissionComponent
{
    public function __construct(private array $permissions) {}

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }
}

class RolePermission implements PermissionComponent
{
    private array $children = [];

    public function __construct(private array $rolePermissions) {}

    public function addChild(PermissionComponent $component): void
    {
        $this->children[] = $component;
    }

    public function hasPermission(string $permission): bool
    {
        if (in_array($permission, $this->rolePermissions)) {
            return true;
        }

        return collect($this->children)
            ->some(fn($child) => $child->hasPermission($permission));
    }
}

// Composite INSIDE Action
class AuthorizePostAction
{
    public function __construct(private PermissionComponent $permissions) {}

    public function execute(User $user, string $requiredPermission): bool
    {
        $userPermissions = new UserPermission($user->permissions);
        return $userPermissions->hasPermission($requiredPermission);
    }
}
```

---

## 6. Facade Pattern (Coordinating Multiple Services)

**Don't confuse with Laravel's Facade class!** This is about simplifying complex workflows.

**When:** Action needs to coordinate multiple services.

```php
// Instead of doing all this in an Action:
class RegisterUserAction
{
    public function execute(UserData $data): User
    {
        // Lots of dependencies and steps...
    }
}

// Extract to Facade class to simplify:
class UserRegistrationFacade
{
    public function __construct(
        private UserService $users,
        private EmailService $emails,
        private AuditService $audit
    ) {}

    public function register(UserData $data): User
    {
        $user = $this->users->create($data);
        $this->emails->sendWelcome($user->email);
        $this->audit->log('user.created', ['user_id' => $user->id]);
        return $user;
    }
}

// In Action - simplified with Facade
class CreateUserAction
{
    public function __construct(private UserRegistrationFacade $facade) {}

    public function execute(UserData $data): User
    {
        return $this->facade->register($data);
    }
}
```

---

## 7. Template Method Pattern

**When:** Action has variations of the same process (import CSV, process report, batch operations).

**Inside Actions:**

- Data import/export workflows (read, validate, transform, save)
- Report generation with different formats
- Batch processing variations

**Example:**

```php
abstract class DataImportAction
{
    // Template defines the skeleton, execute() coordinates all steps
    final public function execute(string $filePath): void
    {
        $data = $this->readFile($filePath);
        $validated = $this->validate($data);
        $transformed = $this->transform($validated);
        $this->save($transformed);
    }

    // Subclasses implement specific steps
    abstract protected function readFile(string $filePath): array;
    abstract protected function validate(array $data): array;
    abstract protected function transform(array $data): array;
    abstract protected function save(array $data): void;
}

class ImportUsersAction extends DataImportAction
{
    protected function readFile(string $filePath): array
    {
        return array_map('str_getcsv', file($filePath));
    }

    protected function validate(array $data): array
    {
        return array_filter($data, fn($row) => !empty($row[0]));
    }

    protected function transform(array $data): array
    {
        return array_map(fn($row) => [
            'name' => $row[0],
            'email' => $row[1],
        ], $data);
    }

    protected function save(array $data): void
    {
        User::insert($data);
    }
}

class ImportProductsAction extends DataImportAction
{
    // Implements same steps but for Products
}
```

---

## Patterns Inside vs Outside Actions

### INSIDE Actions - When Specific to This Action

Use patterns **inside** when they're implementation details specific to this action:

```php
class ProcessPaymentAction
{
    public function __construct(
        private PaymentStrategy $strategy  // Injected
    ) {}

    public function execute(Order $order): Payment
    {
        // Strategy is internal implementation detail
        return $this->strategy->process($order);
    }

    // Private helpers also stay here
    private function validateOrder(Order $order): void
    {
        if ($order->total <= 0) {
            throw new InvalidOrderException();
        }
    }
}
```

**Inside when:**

- Strategy is specific to this action only
- Helper methods are private utilities
- Logic won't be reused elsewhere
- Details are implementation-specific

### OUTSIDE Actions - When Reusable or Generic

Extract patterns **outside** when they're generic, reusable across multiple actions:

```php
// Strategy interface - reusable across multiple actions
interface PaymentStrategy
{
    public function process(Order $order): Payment;
}

// Facade - generic service coordinator (OUTSIDE)
class PaymentFacade
{
    public function __construct(
        private PaymentStrategy $strategy,
        private AuditService $audit,
        private NotificationService $notification
    ) {}

    public function pay(Order $order): Payment
    {
        $payment = $this->strategy->process($order);
        $this->audit->log('payment.processed', ['order_id' => $order->id]);
        $this->notification->notify($order->user, 'Payment processed');
        return $payment;
    }
}

// Action - stays simple by delegating to Facade
class ProcessPaymentAction
{
    public function __construct(private PaymentFacade $facade) {}

    public function execute(Order $order): Payment
    {
        return $this->facade->pay($order);
    }
}
```

**Outside when:**

- Pattern is reused in multiple actions
- Facade coordinates multiple services
- Logic is domain-generic (not action-specific)
- Dependency needs configuration at DI container level

---

## Decision Matrix

| Pattern                      | Location           | Why                                            |
| ----------------------------- | ------------------ | ---------------------------------------------- |
| **Action-specific Strategy** | INSIDE             | Encapsulates implementation details            |
| **Reusable Strategy**        | OUTSIDE (injected) | Used by multiple actions                       |
| **Facade**                   | OUTSIDE            | Usually generic, coordinates multiple services |
| **Private Validation**       | INSIDE             | Local helper, not reused                       |
| **Complex Domain Logic**     | OUTSIDE + INSIDE   | Facade handles coordination, Action executes   |
| **Factory**                  | OUTSIDE (injected) | Creates different implementations              |
| **Adapter**                  | OUTSIDE (injected) | Wraps legacy/external system                   |
| **Composite**                | DEPENDS            | Inside if action-specific, outside if shared   |

---

## The Golden Rule

```
Everything that's specific to this action → INSIDE (private)
Everything that's reusable → OUTSIDE (injected as dependency)
```

---

## Bad Example - Too Much Inside

```php
class ProcessPaymentAction
{
    // Too coupled, hard to test, not reusable
    public function execute(Order $order): Payment
    {
        if (config('payments.provider') === 'stripe') {
            // Stripe logic
        } elseif (config('payments.provider') === 'paypal') {
            // PayPal logic
        }
        // Audit logging
        Log::info(...);
        // Email notification
        Mail::send(...);
        // Inventory update
        $order->items->each(fn($item) => $item->stock--);
    }
}
```

---

## Good Example - Proper Separation

```php
// Facade handles complexity (OUTSIDE)
class PaymentFacade
{
    public function __construct(
        private PaymentProviderFactory $factory,
        private AuditService $audit,
        private NotificationService $notification,
        private InventoryService $inventory
    ) {}

    public function pay(Order $order): Payment
    {
        $provider = $this->factory->create(config('payments.provider'));
        $payment = $provider->process($order);
        $this->audit->log('payment.processed', ['order_id' => $order->id]);
        $this->notification->notifyPayment($order->user, $payment);
        $this->inventory->decreaseStock($order->items);
        return $payment;
    }
}

// Action stays focused (INSIDE execute())
class ProcessPaymentAction
{
    public function __construct(private PaymentFacade $facade) {}

    public function execute(Order $order): Payment
    {
        $this->validateOrder($order);
        return $this->facade->pay($order);
    }

    private function validateOrder(Order $order): void
    {
        if ($order->total <= 0) throw new InvalidOrderException();
    }
}
```

---

## Quick Summary

- **Strategy**: Inject different algorithms
- **Factory**: Create implementations by type
- **Adapter**: Wrap incompatible APIs
- **Observer**: Decouple with events
- **Composite**: Handle hierarchies
- **Facade**: Simplify coordination
- **Template Method**: Reuse algorithm structure

Use them when they solve real problems. Don't over-engineer.
