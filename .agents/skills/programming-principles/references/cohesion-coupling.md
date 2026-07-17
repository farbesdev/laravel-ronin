# Cohesión y Acoplamiento — Principios de Paquetes

Guía de referencia detallada para los principios de cohesión y acoplamiento de Robert C. Martin
(Uncle Bob), aplicados al diseño de módulos en PHP 8.5, TypeScript y la Screaming Architecture
del ERP SaaS.

---

## Principios de Cohesión de Paquetes

Los principios de cohesión responden: **¿Qué clases/componentes van juntos en un módulo?**

---

### REP — Release Reuse Equivalency Principle
> "La granularidad del reúso es la granularidad del release."

**Significado:** Las clases que se versionan y liberan juntas deben agruparse en el mismo
paquete/módulo. Si alguien quiere reutilizar algo de tu módulo, recibe todo el paquete.

**Aplicación práctica:**
```typescript
// ✅ CORRECTO: shared/api/ es un paquete cohesivo que se libera y versiona junto
// Si alguien importa apiClient, también tiene acceso a los interceptores y tipos
// shared/api/
// ├── apiClient.ts       (instancia configurada de $fetch)
// ├── apiClient.types.ts (tipos de respuesta y error)
// └── interceptors.ts    (interceptores de request/response)

// ❌ INCORRECTO: separar apiClient del tipo de respuesta
// Si cambias ApiResponse en otro módulo, apiClient puede quedar desactualizado
```

---

### CCP — Common Closure Principle
> "Las clases que cambian juntas permanecen juntas. Las clases que cambian separadas,
> permanecen separadas."

**Significado:** Es el SRP (Single Responsibility Principle) aplicado a paquetes. Un módulo
debe tener una sola razón para cambiar.

**Aplicación práctica:**
```
✅ modules/security/features/roles/
   Cambia cuando: cambian los requisitos de gestión de roles
   Todo lo que cambia junto (components, stores, services) está en la misma carpeta

❌ Un cambio en "roles" que afecta:
   - components/RoleTable.vue   (en la raíz)
   - stores/rolesStore.ts       (en la raíz)
   - services/rolesApi.ts       (en la raíz)
   Viola CCP: el mismo cambio toca múltiples lugares del proyecto
```

---

### CRP — Common Reuse Principle
> "Las clases que no se reusan juntas no deben estar juntas en el mismo paquete."

**Significado:** No fuerces a los usuarios de tu paquete a depender de cosas que no necesitan.
Si alguien solo usa parte de tu paquete, está obligado a llevar TODO el paquete como dependencia.

**Aplicación práctica:**
```php
// ❌ VIOLA CRP: Paquete "Utils" con todo mezclado
// utils/
// ├── DateHelper.php      (solo lo usa Billing)
// ├── PdfGenerator.php    (solo lo usa Reports)
// ├── CurrencyFormatter.php (solo lo usa Billing)
// └── ImageResizer.php    (solo lo usa Media)

// Si Billing importa DateHelper, también carga el PdfGenerator que no necesita

// ✅ CUMPLE CRP: Paquetes separados por uso
// shared/utils/date/     → DateHelper (importado por Billing y HRM)
// shared/utils/currency/ → CurrencyFormatter (importado por Billing e Invoices)
// shared/utils/pdf/      → PdfGenerator (importado solo por Reports)
```

---

## Principios de Acoplamiento de Paquetes

Los principios de acoplamiento responden: **¿Cómo deben relacionarse los módulos entre sí?**

---

### ADP — Acyclic Dependencies Principle
> "No debe haber ciclos en el grafo de dependencias de paquetes."

**Significado:** Si el módulo A depende de B, B depende de C, y C depende de A, hay un ciclo.
Los ciclos hacen imposible compilar/desplegar módulos de forma independiente.

**Detección:**
```typescript
// ❌ CICLO DETECTADO:
// modules/billing → imports → modules/inventory/types/Product
// modules/inventory → imports → modules/billing/types/Invoice
// (para calcular valor del inventario)

// ✅ ROMPER EL CICLO: Extraer la dependencia a shared/
// shared/types/product.types.ts  → ProductRef { id, name, price }
// modules/billing → imports → shared/types/product.types.ts   ✅
// modules/inventory → imports → shared/types/invoice.types.ts ✅
// Sin ciclo
```

---

### SDP — Stable Dependencies Principle
> "Los módulos deben depender en la dirección de la estabilidad."

**Significado:** Los módulos que cambian frecuentemente (inestables) deben depender de
módulos que cambian poco (estables). Nunca al revés.

```
Estabilidad: shared/api/apiClient.ts  ──────────────── MUY ESTABLE
              (cambia raramente, muchos dependen de él)

Inestabilidad: modules/billing/features/invoices/  ─── INESTABLE
               (cambia frecuentemente según el negocio)

✅ CORRECTO: billing/invoices → depende de → shared/api/ (inestable → estable)
❌ INCORRECTO: shared/api/ → depende de → billing/invoices (estable → inestable)
```

---

### SAP — Stable Abstractions Principle
> "Los paquetes estables deben ser abstractos, y los paquetes inestables deben ser concretos."

**Significado:** Cuanto más estable es un módulo (más dependencias hacia él), más abstracto
debe ser (interfaces, contratos) para poder extenderse sin modificarse.

**Aplicación práctica:**
```php
// shared/ es muy estable → debe contener ABSTRACCIONES
interface HttpClientInterface    // Abstracción estable
interface AuthStoreInterface    // Abstracción estable
interface RepositoryInterface   // Abstracción estable

// modules/billing/ es inestable → puede contener IMPLEMENTACIONES CONCRETAS
final class EloquentInvoiceRepository implements RepositoryInterface // Implementación
final class InvoiceService                                           // Implementación
```

---

## SOLID — Aplicado a PHP y TypeScript

### S — Single Responsibility Principle
```php
// ❌ VIOLA SRP: Controlador con múltiples responsabilidades
class UserController {
    public function store(Request $request) {
        // Validar datos (responsabilidad 1)
        $this->validate($request, [...]);
        // Enviar email (responsabilidad 2)
        Mail::to($request->email)->send(new WelcomeEmail());
        // Crear usuario (responsabilidad 3)
        User::create($request->all());
        // Generar PDF (responsabilidad 4)
        $pdf = PDF::loadView('user.welcome', compact('user'));
    }
}

// ✅ CUMPLE SRP: Cada clase tiene una sola razón para cambiar
final class UserController {
    public function store(StoreUserRequest $request): JsonResponse {
        return UserResource::make(
            $this->createUser->execute(CreateUserDTO::fromRequest($request->validated()))
        )->response()->setStatusCode(201);
    }
}
```

### O — Open/Closed Principle
```typescript
// ✅ Abierto para extensión, cerrado para modificación
// Agregar un nuevo método de pago NO modifica el código existente
interface PaymentGateway {
  charge(amount: number, currency: string): Promise<PaymentResult>
}

class StripeGateway implements PaymentGateway { /* ... */ }
class PaypalGateway implements PaymentGateway { /* ... */ }
class YapeGateway   implements PaymentGateway { /* ... */ }  // ← Extensión sin modificar
```

### D — Dependency Inversion Principle
```php
// ✅ Depender de abstracciones, no de implementaciones concretas
final class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,  // Abstracción
        private readonly PaymentGatewayInterface    $gateway,   // Abstracción
        private readonly EventDispatcherInterface   $events,    // Abstracción
    ) {}
}
```
