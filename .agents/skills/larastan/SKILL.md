---
name: larastan
description: >
  Experto en análisis estático de código Laravel con Larastan (PHPStan para Laravel). Usar cuando
  se instale o configure Larastan, se resuelvan errores de PHPStan nivel 1–9, se escriban stubs
  para Eloquent/Facades/Macros, se configuren baseline, se añadan type hints en modelos y
  colecciones, o se integre Larastan en CI/CD. Activa en "Larastan", "PHPStan", "análisis
  estático PHP", "phpstan.neon", "nivel PHPStan", "baseline PHPStan", "type hints Eloquent"
  o "errores PHPStan".
metadata:
  version: "1.0.0"
  domain: tooling
  triggers: Larastan, PHPStan, análisis estático, phpstan.neon, baseline, Eloquent types, facades stubs, nivel PHPStan, CI análisis
  role: specialist
  scope: implementation
  output-format: code
  related-skills: laravel, php, php-best-practices, phpunit
---

# Larastan — Experto y Buenas Prácticas

Perfil de **Experto en Análisis Estático PHP** con dominio completo de Larastan (PHPStan +
extensiones Laravel), configuración de niveles de reglas, resolución de errores de tipos en
Eloquent, Facades y Macros, y gestión de baselines para proyectos en producción.

---

## 1. Instalación

```bash
# Instalar larastan/larastan (incluye PHPStan)
composer require larastan/larastan --dev

# Verificar versión instalada
vendor/bin/phpstan --version
```

> **Compatibilidad:**
> | Larastan | Laravel | PHPStan | PHP |
> |----------|---------|---------|-----|
> | ^3.0 | 11.x / 12.x / 13.x | ^2.0 | ^8.2 |
> | ^2.0 | 10.x / 11.x | ^1.10 | ^8.1 |

---

## 2. Configuración `phpstan.neon` — Nivel 8

```neon
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    # Nivel de análisis: 0 (permisivo) → 9 (máximo estricto)
    # Nivel 8 recomendado: detecta errores de tipo sin ser extremadamente estricto
    level: 8

    paths:
        - src
        - app

    # Excluir directorios que no se analizan
    excludePaths:
        - vendor
        - tests
        - database/migrations
        - config
        - resources
        - bootstrap

    # Ignorar errores conocidos de paquetes de terceros
    ignoreErrors:
        # Ejemplo: ignorar error específico en un archivo
        # - message: '#Call to undefined method#'
        #   path: src/Legacy/OldClass.php

    # Configuración para paquetes (sin app/)
    bootstrapFiles:
        # Si hay un helper de bootstrap personalizado
        # - tests/bootstrap.php

    # Tipos estrictos para colecciones Eloquent
    checkMissingIterableValueType: false

    # Inferir tipos de relaciones Eloquent
    inferPrivatePropertyTypeFromConstructor: true
```

---

## 3. Niveles de PHPStan — Guía Rápida

| Nivel | Qué verifica |
|-------|-------------|
| **0** | Errores de sintaxis, clases/métodos inexistentes básicos |
| **1** | Variables no definidas, tipos de retorno obvios |
| **2** | Tipos desconocidos en `mixed`, métodos en nullable |
| **3** | Tipos de retorno, tipos de parámetros en llamadas |
| **4** | Tipos en array acceso, verificación de dead code básico |
| **5** | Verificación de tipos en condiciones, `instanceof` |
| **6** | Tipos de retorno estrictos para todos los métodos |
| **7** | Tipos de propiedades de clase, unión de tipos |
| **8** | Nullability estricta, tipos en operaciones de string/int ← **Recomendado** |
| **9** | Máximo: prohíbe `mixed`, verifica todo |

---

## 4. Ejecutar Larastan

```bash
# Análisis estándar con phpstan.neon
vendor/bin/phpstan analyse

# Especificar nivel manualmente
vendor/bin/phpstan analyse --level=8

# Analizar paths específicos
vendor/bin/phpstan analyse src/ app/ --level=8

# Generar reporte legible
vendor/bin/phpstan analyse --error-format=table

# Ver solo resumen de errores (sin detalle)
vendor/bin/phpstan analyse --no-progress
```

---

## 5. Tipos Correctos en Modelos Eloquent

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $special
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 */
final class Role extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'description', 'special'];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
```

> **Regla**: Larastan analiza los PHPDoc de los modelos para inferir tipos de relaciones.
> Sin ellos, las relaciones retornan `mixed` y generan errores en niveles 4+.

---

## 6. Tipos en Colecciones Eloquent

```php
<?php

declare(strict_types=1);

// ✅ Colección tipada — Larastan entiende el tipo de los elementos
/** @var \Illuminate\Database\Eloquent\Collection<int, Role> $roles */
$roles = Role::all();

// ✅ Parámetro tipado en método
/** @param \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions */
public function syncPermissions(Collection $permissions): void
{
    $this->permissions()->sync($permissions->pluck('id')->all());
}

// ✅ Retorno tipado en repositorio
/** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<Role> */
public function paginate(int $perPage = 15): LengthAwarePaginator
{
    return Role::query()->paginate($perPage);
}
```

---

## 7. Gestión de Errores con Baseline

La **baseline** te permite registrar errores existentes y enfocarte en los nuevos.
Ideal para proyectos legacy que migran gradualmente a análisis estático.

```bash
# Generar baseline con todos los errores actuales
vendor/bin/phpstan analyse --generate-baseline

# Esto crea phpstan-baseline.neon con todos los errores actuales
```

```neon
# phpstan.neon — incluir baseline
includes:
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon   # ← Añadir aquí

parameters:
    level: 8
    paths:
        - src
        - app
```

```bash
# Verificar que no hay errores nuevos (solo los de baseline)
vendor/bin/phpstan analyse

# Si añades código nuevo sin errores: salida limpia ✅
# Si introduces un error nuevo: PHPStan falla ❌
```

> **Regla**: Nunca versionar código nuevo en baseline. Usarla solo para código legacy.
> El objetivo es ir **reduciendo** la baseline hasta que desaparezca.

---

## 8. Resolver Errores Comunes en Proyectos Laravel

### Error: `Call to an undefined method ... ::factory()`

```neon
# phpstan.neon
parameters:
    laravel:
        noUnnecessaryCollectionCall: false
```

```php
// Alternativa: añadir @method en el modelo
/**
 * @method static \Illuminate\Database\Eloquent\Factories\Factory factory()
 */
class User extends Model { ... }
```

### Error: `Property ... does not exist on ... Model`

```php
// ✅ Documentar propiedades con @property en el modelo
/**
 * @property int $id
 * @property string $name
 */
class User extends Model { ... }
```

### Error: `Parameter #1 $closure ... expects Closure, ... given`

```php
// ✅ Tipar el closure explícitamente
$query->when(
    $this->search,
    /** @param \Illuminate\Database\Eloquent\Builder<Role> $q */
    fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('name', 'like', "%{$this->search}%")
);
```

### Error: `Method ... should return ... but returns mixed`

```php
// ❌ Sin tipo
public function getModel()
{
    return app()->make(config('shinobi.models.role'));
}

// ✅ Con tipo correcto
public function getModel(): Role
{
    /** @var Role $model */
    $model = app()->make(config('shinobi.models.role'));
    return $model;
}
```

### Error en Facades — `Call to undefined method ... ::staticMethod()`

```neon
# phpstan.neon — ignorar errores de facades dinámicas
parameters:
    ignoreErrors:
        - '#Call to undefined static method Illuminate\\Support\\Facades\\.*#'
```

---

## 9. CI/CD con GitHub Actions

```yaml
# .github/workflows/analysis.yml
name: Static Analysis

on: [push, pull_request]

jobs:
  phpstan:
    name: PHPStan Level 8
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer:v2

      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Run Larastan
        run: vendor/bin/phpstan analyse --level=8 --no-progress

      - name: Upload PHPStan results
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: phpstan-results
          path: phpstan-baseline.neon
```

---

## 10. Scripts en `composer.json`

```json
{
    "scripts": {
        "analyse": "vendor/bin/phpstan analyse --level=8",
        "analyse:baseline": "vendor/bin/phpstan analyse --generate-baseline",
        "test": "vendor/bin/phpunit",
        "ci": ["@analyse", "@test"]
    },
    "scripts-descriptions": {
        "analyse": "Ejecuta análisis estático con Larastan nivel 8",
        "analyse:baseline": "Genera la baseline de errores existentes",
        "ci": "Ejecuta análisis estático + tests (para pipelines CI)"
    }
}
```

```bash
# Uso
composer analyse          # Analizar con nivel 8
composer analyse:baseline # Generar baseline
composer ci               # Análisis + tests completo
```

---

## 11. Checklist de Buenas Prácticas

### Configuración
- [ ] `phpstan.neon` en raíz del proyecto, versionado en Git
- [ ] Incluir `vendor/larastan/larastan/extension.neon`
- [ ] Nivel 8 mínimo para proyectos nuevos
- [ ] `excludePaths` incluye `vendor`, `database/migrations`, `bootstrap`
- [ ] `phpstan-baseline.neon` solo para código legacy, nunca para código nuevo

### Modelos Eloquent
- [ ] PHPDoc `@property` para todas las columnas de la tabla
- [ ] PHPDoc `@property-read` para relaciones (`BelongsToMany`, `HasMany`, etc.)
- [ ] Tipos de retorno explícitos en métodos de relación: `BelongsToMany<Permission, $this>`
- [ ] `@var` en variables que reciben resultado de `app()->make()` o config dinámica

### Calidad
- [ ] `declare(strict_types=1)` en todos los archivos PHP
- [ ] No usar `mixed` — siempre ser específico con los tipos
- [ ] Resolver errores reales en lugar de ignorarlos globalmente
- [ ] Reducir `phpstan-baseline.neon` con cada sprint
- [ ] PHPStan pasa limpio en CI antes de merge a `main`

### Para Paquetes
- [ ] Analizar solo `src/` (no `tests/`)
- [ ] Añadir `vendor/bin/phpstan analyse` al script `ci` en `composer.json`
- [ ] Verificar compatibilidad de versión Larastan con Laravel en matriz de CI
