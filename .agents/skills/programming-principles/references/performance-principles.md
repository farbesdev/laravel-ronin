# Principios de Optimización y Rendimiento

Guía de referencia para tomar decisiones de optimización basadas en evidencia, no en intuición.
Aplicado al stack ERP SaaS: PHP 8.5, Node.js/Nuxt 4 y PostgreSQL.

---

## Premature Optimization is the Root of All Evil (Knuth, 1974)

> "Los programadores gastan una enorme cantidad de tiempo pensando, o preocupándose por, la
> velocidad de las partes no críticas de sus programas, y esos intentos de eficiencia en
> realidad tienen un fuerte impacto negativo cuando se considera la depuración y el
> mantenimiento. Debemos olvidar las pequeñas eficiencias, digamos el 97% del tiempo:
> la optimización prematura es la raíz de todos los males."

### Cuándo NO optimizar
```
¿Hay un problema de rendimiento medido?
├── NO → No optimizar. Priorizar legibilidad y mantenibilidad.
└── SÍ → ¿Sabemos exactamente dónde está el cuello de botella?
           ├── NO → Medir primero (profiling, APM, tracing)
           └── SÍ → Optimizar SOLO ese cuello de botella
```

### Cuándo SÍ es válido optimizar desde el diseño
```typescript
// ✅ Optimización de diseño (no prematura): elegir la estructura de datos correcta
// Búsqueda en O(1) vs O(n) es una decisión de diseño, no micro-optimización
const permissionsMap = new Map(permissions.map(p => [p.name, p]))  // O(1) lookup
const hasPermission = (name: string) => permissionsMap.has(name)   // vs O(n) find()

// ✅ Evitar trabajo innecesario desde el inicio (no es prematura)
// No cargar 10,000 registros para mostrar 15
const roles = await rolesApi.index({ page: 1, perPage: 15 })  // ✅ Paginación desde el inicio

// ❌ Micro-optimización prematura
// "Uso for en lugar de forEach porque es 0.1ms más rápido"
// (sin evidencia de que ese loop sea el cuello de botella)
```

---

## Amdahl's Law (Gene Amdahl, 1967)

> "La mejora máxima de rendimiento de un sistema al mejorar un componente está limitada por
> la fracción del tiempo en que ese componente está en uso."

### Fórmula
```
Speedup máximo = 1 / ((1 - P) + P/N)

Donde:
  P = fracción del programa que se puede paralelizar
  N = número de procesadores/workers
```

### Aplicación práctica

```php
// Si el 80% del tiempo se puede paralelizar con N=4 workers:
// Speedup = 1 / ((1 - 0.8) + 0.8/4) = 1 / (0.2 + 0.2) = 2.5x (no 4x)

// Implicación: antes de agregar más workers, optimizar el 20% no paralelizable
// (conexión a DB, memoria compartida, locks)

// ✅ Usar Jobs en paralelo para procesamiento intensivo
// Solo cuando ese procesamiento sea el cuello de botella MEDIDO
Bus::batch([
    new ProcessTenantReport($tenantA),
    new ProcessTenantReport($tenantB),
    new ProcessTenantReport($tenantC),
])->dispatch();
```

---

## Mechanical Sympathy (Martin Thompson)

> "Comprende cómo funciona el hardware para escribir software que trabaje con él, no contra él."

### CPU Cache Lines

```php
// ❌ Cache unfriendly: acceso en columnas (salta en memoria)
for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $m; $j++) {
        $sum += $matrix[$j][$i];  // Acceso por columna = muchos cache misses
    }
}

// ✅ Cache friendly: acceso en filas (secuencial en memoria)
for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $m; $j++) {
        $sum += $matrix[$i][$j];  // Acceso por fila = prefetch del CPU
    }
}
```

### Branch Prediction

```php
// ❌ Branches impredecibles (datos no ordenados)
foreach ($randomStatusList as $status) {
    if ($status === 'active') {
        $activeCount++;
    }
}

// ✅ Reducir branches (operación sin condicional)
$activeCount = count(array_filter($randomStatusList, fn($s) => $s === 'active'));

// ✅ O mejor: agregar en la BD (el más eficiente)
$activeCount = Role::where('status', 'active')->count();  // El motor BD es más eficiente
```

### Memory Locality en JavaScript

```typescript
// ✅ Agrupar propiedades frecuentemente accesadas juntas
// El V8 JIT optimiza objetos con forma consistente (hidden classes)
interface ListItem {
  id:     number   // Accedido en cada render
  name:   string   // Accedido en cada render
  status: string   // Accedido en cada render
  // Propiedades menos frecuentes al final
  description?: string
  metadata?:    Record<string, unknown>
}

// ❌ No cambiar la "forma" del objeto en runtime
const item = { id: 1, name: 'Admin' }
item.newProp = 'value'  // Crea una nueva hidden class → deoptimiza V8
```

---

## Little's Law (John Little, 1961)

> "El número promedio de elementos en un sistema = tasa de llegada × tiempo promedio de
> procesamiento."

**Fórmula:** `L = λ × W`

Donde:
- `L` = número de elementos en el sistema (concurrencia)
- `λ` = tasa de llegada (requests/segundo)
- `W` = tiempo de procesamiento (segundos)

```
Ejemplo real:
- API recibe 100 req/s (λ = 100)
- Cada request tarda 0.5s en procesarse (W = 0.5)
- L = 100 × 0.5 = 50 requests simultáneos en el sistema

Implicación: necesitas manejar 50 conexiones concurrentes mínimo.
Para reducir L: reducir W (optimizar el endpoint) o reducir λ (rate limiting, caché)
```

---

## Reglas Prácticas de Optimización

### Para PHP / Laravel
```bash
# 1. Medir primero (NUNCA optimizar a ciegas)
php artisan telescope:install   # Laravel Telescope para profiling
# o
php artisan horizon:install     # Para queues

# 2. Query optimization
# N+1 Query Problem → eager loading
User::with(['roles', 'permissions'])->paginate(15)  # ✅ 3 queries
User::paginate(15)  # ❌ 1 + N*2 queries

# 3. Cachear lo que cambia poco y cuesta mucho
Cache::remember('tenant_roles', 3600, fn() => Role::with('permissions')->get())

# 4. Índices en columnas de búsqueda frecuente
$table->index(['tenant_id', 'status']);     // Índice compuesto
$table->index('email');                     // Búsqueda por email
```

### Para Nuxt / Frontend
```typescript
// 1. Medir con Vue DevTools Performance tab

// 2. shallowRef para colecciones grandes (evita reactividad profunda)
const bigList = shallowRef<BigItem[]>([])

// 3. computed cacheado (no recalcula si las deps no cambian)
const sortedItems = computed(() => [...items.value].sort(compareFn))

// 4. v-memo para evitar re-renders costosos
// <HeavyRow v-memo="[item.id, item.updatedAt]" :item="item" />

// 5. Virtualización para listas largas
// <v-virtual-scroll :items="bigList" item-height="64"> ... </v-virtual-scroll>
```

---

## Checklist de Decisiones de Rendimiento

- [ ] ¿Tenemos evidencia (métrica) de que hay un problema de rendimiento?
- [ ] ¿Sabemos exactamente qué parte del código es el cuello de botella? (profiling)
- [ ] ¿Hemos aplicado primero optimizaciones de diseño? (índices, paginación, caché)
- [ ] ¿La optimización vale el costo en legibilidad y mantenibilidad?
- [ ] ¿Hemos medido el impacto de la optimización después de aplicarla?
- [ ] ¿Hay un test de regresión de rendimiento que detecte retrocesos futuros?
