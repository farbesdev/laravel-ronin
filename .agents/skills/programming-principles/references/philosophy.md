# Filosofía de Programación y Diseño

Guía de referencia de los principios filosóficos que guían las decisiones de diseño de
software en el proyecto ERP SaaS — cuándo aplicarlos y cuándo resistir la tentación de
aplicarlos prematuramente.

---

## Worse is Better (Richard Gabriel, 1991)

> "Es mejor tener una implementación simple e incompleta que una compleja y completa."

### Los 4 Atributos (en orden de prioridad para "Worse is Better")

| Atributo | MIT "The Right Thing" | New Jersey "Worse is Better" |
|---|---|---|
| **Simplicidad** | No sacrificarla (interfaz O implementación) | Más importante que todo (PRIORIDAD MÁXIMA) |
| **Corrección** | En todos los casos | Solo en casos comunes; sin ser incorrecta |
| **Consistencia** | Requerida | Puede sacrificarse por simplicidad |
| **Completitud** | Todos los casos importantes | Puede omitir casos si complica demasiado |

**Aplicación en el proyecto:**
```typescript
// ✅ "Worse is Better" — Solución simple que funciona en el 95% de casos
function formatDate(date: string | Date): string {
  return new Date(date).toLocaleDateString('es-PE')
}

// ❌ "The Right Thing" — Solución completa pero sobreingeniería para el contexto actual
class InternationalDateFormatter {
  constructor(
    private locale: Intl.LocalesArgument,
    private timezone: string,
    private calendar: Intl.DateTimeFormatOptions['calendar'],
    private numberingSystem: string,
    // ... 20 opciones más
  ) {}
  // 300 líneas de código para formatear una fecha
}
```

---

## SoC — Separation of Concerns (Dijkstra, 1974)

> "Organiza el sistema de manera que cada parte se ocupe de una sola preocupación."

### Las "Concerns" del ERP SaaS

| Capa | Preocupación | Ubicación |
|---|---|---|
| **Presentación** | Cómo se ve | `components/`, `pages/` |
| **Lógica de UI** | Cómo interactúa el usuario | `composables/` |
| **Estado** | Qué datos existen en memoria | `stores/` |
| **Comunicación** | Cómo habla con el backend | `services/` |
| **Dominio (BE)** | Reglas del negocio | `Actions/`, `Services/` PHP |
| **Infraestructura** | Cómo se persisten los datos | `Repositories/` PHP |

```typescript
// ❌ VIOLA SoC: Componente Vue que hace todo
<script setup lang="ts">
// Lógica de negocio mezclada con UI
const roles = ref([])
onMounted(async () => {
  const res = await $fetch('/api/security/roles')  // Comunicación directa
  roles.value = res.data.filter(r => r.guard === 'api')  // Lógica de negocio
  document.title = `${roles.value.length} roles`  // Efecto secundario
})
</script>

// ✅ CUMPLE SoC: Cada capa en su lugar
// store → gestiona los datos
// service → se comunica con la API
// composable → orquesta store + estado local de UI
// componente → solo renderiza
```

---

## PoLA — Principle of Least Astonishment

> "El componente debe comportarse de la manera que los usuarios esperan."

```typescript
// ❌ VIOLA PoLA: Nombre que miente
function getUser(id: number): User[] {  // "get" implica uno, retorna muchos
  return users.filter(u => u.groupId === id)
}

// ❌ VIOLA PoLA: Efecto secundario inesperado
function formatCurrency(amount: number): string {
  analytics.track('currency_formatted', { amount })  // ¡Un formateador que trackea!
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(amount)
}

// ✅ CUMPLE PoLA: Nombre claro, sin sorpresas
function getUserById(id: number): User | undefined {
  return users.find(u => u.id === id)
}

function getUsersByGroupId(groupId: number): User[] {
  return users.filter(u => u.groupId === groupId)
}

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(amount)
}
```

---

## YAGNI — You Aren't Gonna Need It (XP)

> "No implementes algo hasta que lo necesites."

```typescript
// ❌ VIOLA YAGNI: Implementar "por si acaso"
interface UserRepository {
  findById(id: number): Promise<User>
  findAll(): Promise<User[]>
  findByEmail(email: string): Promise<User | null>
  findByRole(role: string): Promise<User[]>      // No se usa todavía
  findByCreatedAfter(date: Date): Promise<User[]> // No se usa todavía
  findWithPagination(opts: PaginationOpts): Promise<PaginatedResult<User>> // No se usa
  exportToCsv(): Promise<string>                  // YAGNI extremo
}

// ✅ CUMPLE YAGNI: Solo lo que se necesita hoy
interface UserRepository {
  findById(id: number): Promise<User>
  findByEmail(email: string): Promise<User | null>
  create(data: CreateUserInput): Promise<User>
}
// Agregar métodos cuando se necesiten, no antes
```

---

## DRY — Don't Repeat Yourself (Hunt & Thomas, "The Pragmatic Programmer")

> "Cada pieza de conocimiento debe tener una representación única, no ambigua y autoritativa
> dentro de un sistema."

**⚠️ Importante:** DRY no significa "no duplicar código". Significa "no duplicar conocimiento".
Dos funciones con el mismo código pero que representan conceptos diferentes NO violan DRY.

```typescript
// ❌ FALSO POSITIVO: Estas funciones parecen duplicadas pero son conceptos distintos
function validateUserAge(age: number): boolean {
  return age >= 18
}

function validateLegalDrivingAge(age: number): boolean {
  return age >= 18  // Mismo código, DIFERENTE regla de negocio
}

// Si la edad para votar cambia a 16, NO deben cambiar juntas.
// ¡Abstraerlas en una sola función sería INCORRECTO!

// ✅ VERDADERA DUPLICACIÓN: La misma regla de negocio en dos lugares
// modules/billing/utils.ts
function formatSolesToCentimos(amount: number): number { return Math.round(amount * 100) }

// modules/payments/utils.ts
function convertToCentimos(amount: number): number { return Math.round(amount * 100) }
// → ESTO sí viola DRY: misma regla, dos implementaciones
// Mover a: shared/utils/currency.ts
```

---

## PIE — Program Intently and Expressively

> "El código debe expresar claramente la intención del programador."

```php
// ❌ Código que requiere descifrado
$r = $u->p->where('g', 'api')->pluck('n')->toArray();

// ✅ Código que expresa intención
$apiPermissions = $user->permissions
    ->where('guard', 'api')
    ->pluck('name')
    ->toArray();
```

---

## EDD — Error-Driven Development

> "Deja que los errores te guíen: escribe el código más simple, deja que falle, corrige solo
> lo que falla."

**Aplicación práctica:**
1. Escribe la implementación más simple (incluso hardcoded)
2. Ejecuta las pruebas → observa el error
3. Corrige exactamente ese error
4. Repite hasta que todas las pruebas pasen
5. Refactoriza
