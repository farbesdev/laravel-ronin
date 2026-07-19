---
name: design-patterns
description: >
  Experto en Patrones de Diseño (GoF) aplicados a PHP 8.5, JavaScript y TypeScript. Usar cuando
  se identifiquen o implementen patrones creacionales (Factory, Builder, Singleton, Prototype,
  Abstract Factory), estructurales (Adapter, Bridge, Composite, Decorator, Facade, Flyweight,
  Proxy) o de comportamiento (Chain of Responsibility, Command, Iterator, Mediator, Memento,
  Observer, State, Strategy, Template Method, Visitor). Activa en "patrón de diseño",
  "Factory", "Strategy", "Observer", "Decorator", "Repository" o "CQRS".
---

# Patrones de Diseño (GoF) — PHP 8.5, JavaScript y TypeScript

Perfil de **Experto en Patrones de Diseño** con aplicación práctica de los 23 patrones GoF
y patrones modernos de arquitectura enterprise en el contexto del stack ERP SaaS.

---

## 1. Patrones Creacionales

### Factory Method — Crear objetos sin especificar la clase exacta

```php
// PHP 8.5 — Factory de notificaciones
interface Notification
{
    public function send(string $message, string $recipient): void;
}

abstract class NotificationFactory
{
    abstract protected function createNotification(): Notification;

    // Template method — usa el factory method
    final public function notify(string $message, string $recipient): void
    {
        $notification = $this->createNotification();
        $notification->send($message, $recipient);
    }
}

final class EmailNotificationFactory extends NotificationFactory
{
    protected function createNotification(): Notification
    {
        return new EmailNotification(config('mail.from'));
    }
}

final class SlackNotificationFactory extends NotificationFactory
{
    protected function createNotification(): Notification
    {
        return new SlackNotification(config('services.slack.webhook'));
    }
}
```

```typescript
// TypeScript — Factory de API clients
interface HttpClient {
  get<T>(url: string): Promise<T>
  post<T>(url: string, body: unknown): Promise<T>
}

function createHttpClient(type: 'fetch' | 'axios'): HttpClient {
  return match(type, {
    fetch: () => new FetchClient(),
    axios: () => new AxiosClient(),
  })
}
```

---

### Builder — Construir objetos complejos paso a paso

```php
// PHP 8.5 — Builder de queries
final class RoleQueryBuilder
{
    private string $guard     = 'api';
    private ?string $search   = null;
    private int $perPage      = 15;
    private string $sortBy    = 'name';
    private string $sortDir   = 'asc';

    public function withGuard(string $guard): self
    {
        $clone = clone $this;
        $clone->guard = $guard;
        return $clone;  // Immutable builder
    }

    public function search(string $term): self
    {
        $clone = clone $this;
        $clone->search = $term;
        return $clone;
    }

    public function paginate(int $perPage): self
    {
        $clone = clone $this;
        $clone->perPage = $perPage;
        return $clone;
    }

    public function build(): \Illuminate\Database\Eloquent\Builder
    {
        return Role::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->where('guard', $this->guard)
            ->orderBy($this->sortBy, $this->sortDir);
    }
}

// Uso fluido
$roles = (new RoleQueryBuilder())
    ->withGuard('api')
    ->search('admin')
    ->paginate(20)
    ->build()
    ->paginate(20);
```

---

## 2. Patrones Estructurales

### Decorator — Añadir responsabilidades dinámicamente

```php
// PHP 8.5 — Decorator de caché sobre repositorio
interface RoleRepository
{
    public function findAll(): array;
    public function findById(int $id): ?Role;
}

final class CachedRoleRepository implements RoleRepository
{
    public function __construct(
        private readonly RoleRepository $inner,
        private readonly \Illuminate\Contracts\Cache\Repository $cache,
        private readonly int $ttl = 3600,
    ) {}

    public function findAll(): array
    {
        return $this->cache->remember('roles.all', $this->ttl, fn() => $this->inner->findAll());
    }

    public function findById(int $id): ?Role
    {
        return $this->cache->remember("roles.{$id}", $this->ttl, fn() => $this->inner->findById($id));
    }
}
```

```typescript
// TypeScript — Decorator de logs en composable
function withLogging<T extends Record<string, (...args: unknown[]) => unknown>>(
  composable: () => T,
  prefix: string,
): () => T {
  return () => {
    const instance = composable()

    return new Proxy(instance, {
      get(target, prop) {
        const original = target[prop as string]
        if (typeof original !== 'function') return original

        return (...args: unknown[]) => {
          console.log(`[${prefix}] ${String(prop)}(`, args, ')')
          return original.apply(target, args)
        }
      },
    }) as T
  }
}
```

---

### Facade — Interfaz simplificada para un subsistema complejo

```php
// PHP 8.5 — Facade de autenticación
final class AuthFacade
{
    public function __construct(
        private readonly UserRepository     $users,
        private readonly TokenService       $tokens,
        private readonly AuditLogService    $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function login(string $email, string $password): AuthResult
    {
        $user = $this->users->findByEmail($email)
            ?? throw new UserNotFoundException();

        if (!$this->tokens->verify($password, $user->password)) {
            $this->audit->logFailedLogin($email);
            throw new InvalidCredentialsException();
        }

        $token = $this->tokens->createForUser($user);
        $this->audit->logSuccessfulLogin($user);
        $this->notifications->sendLoginAlert($user);

        return new AuthResult($user, $token);
    }
}
```

---

## 3. Patrones de Comportamiento

### Strategy — Algoritmos intercambiables

```php
// PHP 8.5 — Strategy de descuentos
interface DiscountStrategy
{
    public function calculate(float $amount, array $context): float;
}

final class PercentageDiscount implements DiscountStrategy
{
    public function __construct(private readonly float $percentage) {}

    public function calculate(float $amount, array $context): float
    {
        return $amount * ($this->percentage / 100);
    }
}

final class FixedDiscount implements DiscountStrategy
{
    public function __construct(private readonly float $amount) {}

    public function calculate(float $amount, array $context): float
    {
        return min($this->amount, $amount);
    }
}

final class PriceCalculator
{
    public function __construct(
        private readonly DiscountStrategy $discount,
    ) {}

    public function calculate(float $basePrice, array $context = []): float
    {
        return $basePrice - $this->discount->calculate($basePrice, $context);
    }
}
```

```typescript
// TypeScript — Strategy de ordenamiento de tablas
type SortStrategy<T> = (a: T, b: T) => number

const sortStrategies = {
  alphabetical: <T extends { name: string }>(a: T, b: T) =>
    a.name.localeCompare(b.name),

  byDate: <T extends { createdAt: string }>(a: T, b: T) =>
    new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),

  byId: <T extends { id: number }>(a: T, b: T) => a.id - b.id,
} satisfies Record<string, SortStrategy<never>>
```

---

### Observer — Notificación de cambios de estado

```php
// PHP 8.5 — Observer con eventos de Laravel
// Evento
final class RoleCreated
{
    public function __construct(
        public readonly Role $role,
        public readonly User $createdBy,
    ) {}
}

// Listener
final class SendRoleCreatedNotification
{
    public function handle(RoleCreated $event): void
    {
        Notification::send(
            User::admins()->get(),
            new RoleCreatedNotification($event->role, $event->createdBy),
        );
    }
}

// En el Action
event(new RoleCreated($role, auth()->user()));
```

---

### Command — Encapsular operaciones como objetos

```typescript
// TypeScript — Command pattern para acciones reversibles
interface Command {
  execute(): Promise<void>
  undo():    Promise<void>
}

class DeleteRoleCommand implements Command {
  private deletedRole: Role | null = null

  constructor(
    private readonly roleId: number,
    private readonly api: typeof rolesApi,
    private readonly store: ReturnType<typeof useRolesStore>,
  ) {}

  async execute(): Promise<void> {
    this.deletedRole = this.store.findById(this.roleId)
    await this.api.destroy(this.roleId)
    this.store.remove(this.roleId)
  }

  async undo(): Promise<void> {
    if (!this.deletedRole) return
    const restored = await this.api.store({
      name:        this.deletedRole.name,
      guard:       this.deletedRole.guard,
      permissions: this.deletedRole.permissions,
    })
    this.store.add(restored.data)
  }
}

// History de comandos para undo/redo
class CommandHistory {
  private readonly history: Command[] = []

  async execute(command: Command): Promise<void> {
    await command.execute()
    this.history.push(command)
  }

  async undo(): Promise<void> {
    const command = this.history.pop()
    await command?.undo()
  }
}
```

---

## 4. Cuándo Usar Cada Patrón

| Patrón | Usar cuando... |
|---|---|
| **Factory Method** | Necesitas crear objetos pero no sabes el tipo exacto hasta runtime |
| **Builder** | Construyes objetos con muchos parámetros opcionales |
| **Singleton** | Solo debe existir una instancia (con cuidado: dificulta testing) |
| **Decorator** | Quieres añadir comportamiento sin modificar la clase base |
| **Facade** | Tienes un subsistema complejo y quieres una interfaz simple |
| **Proxy** | Necesitas controlar acceso, añadir caché o lazy loading |
| **Strategy** | Tienes algoritmos intercambiables en runtime |
| **Observer** | Necesitas notificar a múltiples objetos de un cambio de estado |
| **Command** | Necesitas operaciones reversibles (undo/redo) o colas de acciones |
| **Template Method** | Tienes un algoritmo con pasos fijos pero implementaciones variables |
| **Repository** | Quieres abstraer el acceso a datos de la lógica de negocio |

---

## 5. Checklist de Patrones de Diseño

- [ ] Identificar el problema antes de aplicar el patrón
- [ ] Preferir la solución más simple que funcione (no sobreingeniería)
- [ ] Los patrones deben emerger del diseño, no imponerse
- [ ] Documentar por qué se eligió un patrón específico
- [ ] Testear el comportamiento, no la implementación del patrón
