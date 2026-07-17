---
name: php
description: >
  Experto en PHP 8.5 y buenas prácticas. Usar cuando se escriba o revise código PHP moderno —
  tipos nativos, enums, readonly, fibers, hooks de propiedades, operador pipe, atributos,
  patrones funcionales y PSR standards. Activa en "PHP 8.5", "PHP moderno", "tipos PHP",
  "readonly PHP", "enum PHP", "pipe operator" o "property hooks".
---

# PHP 8.5 — Experto y Buenas Prácticas

Perfil de **Experto PHP 8.5** con dominio total de las características modernas del lenguaje,
PSR standards y patrones idiomáticos para código limpio, tipado estricto y de alta performance.

---

## 1. Fundamentos Obligatorios

```php
<?php
declare(strict_types=1);  // SIEMPRE — primera línea en todos los archivos
```

---

## 2. Sistema de Tipos Completo

### Constructor Promotion + Readonly (8.1+)
```php
final readonly class UserDTO
{
    public function __construct(
        public string  $id,
        public string  $name,
        public string  $email,
        public ?string $avatarUrl = null,
    ) {}
}
```

### Union Types, Intersection Types y DNF Types (8.2+)
```php
function processInput(int|string $id): User|null { /* ... */ }

// Intersection — objeto que implementa AMBAS interfaces
function log(Stringable&Countable $data): void { /* ... */ }

// DNF Types (8.2+) — combinación de unión e intersección
function handle((Stringable&Countable)|null $data): string { /* ... */ }
```

### Property Hooks (8.4+)
```php
class Product
{
    // Hook set: transforma el valor al asignarlo
    public string $name {
        set => trim($value);
    }

    // Hook get: computa el valor al leerlo
    public float $priceWithTax {
        get => $this->price * 1.18;
    }

    // Ambos hooks
    public string $slug {
        get => strtolower(str_replace(' ', '-', $this->name));
        set => $this->name = $value;
    }

    public function __construct(
        public float $price,
    ) {}
}
```

### Asymmetric Visibility (8.4+)
```php
class Order
{
    // Pública para lectura, privada para escritura (solo el propio objeto la asigna)
    public private(set) string $status = 'pending';

    public function approve(): void
    {
        $this->status = 'approved';  // ✅ Dentro de la clase
    }
}

$order = new Order();
echo $order->status;   // ✅ Lectura pública
$order->status = 'x';  // ❌ Error: escritura privada
```

### Pipe Operator (8.5+)
```php
$result = '  Hola Mundo  '
    |> trim(...)
    |> strtolower(...)
    |> str_replace(' ', '-', ...)
    |> htmlspecialchars(...);
```

---

## 3. Enums (8.1+)

```php
enum OrderStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pendiente',
            self::Approved  => 'Aprobado',
            self::Shipped   => 'En camino',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], strict: true);
    }

    /** @return list<self> */
    public static function active(): array
    {
        return array_filter(self::cases(), fn(self $s) => !$s->isTerminal());
    }
}
```

---

## 4. Match Expression vs Switch

```php
// ❌ Switch: fall-through peligroso, no retorna valor directamente
switch ($status) {
    case 'active': $label = 'Activo'; break;
    default:       $label = 'Desconocido';
}

// ✅ Match: exhaustivo, sin fall-through, retorna valor
$label = match($status) {
    'active'   => 'Activo',
    'inactive' => 'Inactivo',
    'banned'   => 'Bloqueado',
    default    => throw new \ValueError("Estado desconocido: {$status}"),
};
```

---

## 5. Atributos (8.0+)

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
final class RequiresPermission
{
    public function __construct(
        public readonly string $permission,
    ) {}
}

class RoleController
{
    #[RequiresPermission('manage:roles')]
    public function store(StoreRoleRequest $request): JsonResponse { /* ... */ }
}
```

---

## 6. Fibers (8.1+)

```php
// Fibers para concurrencia cooperativa (sin paralelismo real)
$fiber = new \Fiber(function (): void {
    $value = \Fiber::suspend('primer resultado');
    echo "Fiber recibió: {$value}\n";
});

$first = $fiber->start();       // 'primer resultado'
$fiber->resume('hola fiber');   // 'Fiber recibió: hola fiber'
```

---

## 7. Generadores para Grandes Conjuntos de Datos

```php
/** @return \Generator<int, array<string, mixed>> */
function readCsvLazy(string $path): \Generator
{
    $handle = fopen($path, 'r');
    try {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            yield array_combine($headers, $row);
        }
    } finally {
        fclose($handle);
    }
}

// Procesa millones de filas sin cargar todo en memoria
foreach (readCsvLazy('/data/productos.csv') as $row) {
    processRow($row);
}
```

---

## 8. Manejo de Errores

```php
// ❌ Nunca: capturar \Exception genérica
try {
    $user = $repo->findOrFail($id);
} catch (\Exception $e) {
    // Captura CUALQUIER cosa, incluso errores que no deberías manejar
}

// ✅ Correcto: excepciones específicas
try {
    $user = $repo->findOrFail($id);
} catch (\App\Exceptions\UserNotFoundException $e) {
    return response()->json(['message' => $e->getMessage()], 404);
} catch (\App\Exceptions\DatabaseException $e) {
    logger()->critical('Error DB', ['exception' => $e]);
    return response()->json(['message' => 'Error interno'], 500);
} finally {
    // Limpieza garantizada
}
```

---

## 9. Checklist de Buenas Prácticas PHP 8.5

- [ ] `declare(strict_types=1)` en todos los archivos
- [ ] Constructor promotion para propiedades de clase
- [ ] `readonly` para DTOs y Value Objects inmutables
- [ ] `enum` en lugar de constantes mágicas
- [ ] `match` en lugar de `switch`
- [ ] Property Hooks (8.4+) en lugar de getters/setters triviales
- [ ] Asymmetric Visibility (8.4+) para propiedades controladas
- [ ] Pipe Operator (8.5+) para cadenas de transformaciones
- [ ] Generadores para datasets grandes
- [ ] Tipos de retorno en todos los métodos (incluyendo `void`, `never`)
- [ ] Atributos PHP para metadatos en lugar de docblocks
- [ ] Excepciones específicas y jerarquizadas
- [ ] `finally` para limpieza de recursos
