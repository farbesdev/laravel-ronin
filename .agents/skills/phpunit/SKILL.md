---
name: phpunit
description: >
  Experto en pruebas unitarias e integración con PHPUnit para PHP 8.2+. Usar cuando se escriban,
  revisen o diagnostiquen tests con PHPUnit, se configure phpunit.xml, se usen atributos PHP 8
  (#[Test], #[DataProvider], #[Before]), se implementen mocks y stubs, se mida cobertura de código
  o se integren tests con Orchestra Testbench en paquetes Laravel. Activa en "PHPUnit", "test
  unitario", "phpunit.xml", "#[Test]", "DataProvider", "mock PHPUnit", "cobertura de código",
  "Orchestra Testbench" o "TDD en PHP".
metadata:
  version: "1.0.0"
  domain: testing
  triggers: PHPUnit, test unitario, phpunit.xml, DataProvider, mock, stub, TestCase, cobertura, TDD, Orchestra Testbench, RefreshDatabase
  role: specialist
  scope: implementation
  output-format: code
  related-skills: laravel, php, php-best-practices, composer-packages
---

# PHPUnit — Experto y Buenas Prácticas (PHP 8.2+)

Perfil de **Experto en Testing PHP** con dominio total de PHPUnit 10/11/12/13, arquitectura
de pruebas en capas, mocking avanzado, cobertura de código y pruebas de integración con
Orchestra Testbench para paquetes Laravel.

---

## 1. Configuración Canónica `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="vendor/autoload.php"
    colors="true"
    backupGlobals="false"
    backupStaticProperties="false"
    processIsolation="false"
    stopOnFailure="false"
    cacheDirectory=".phpunit.cache"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>

    <!-- Cobertura: qué archivos analizar -->
    <source>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>src/Facades</directory>
        </exclude>
    </source>

    <logging>
        <junit outputFile="build/report.junit.xml"/>
    </logging>

    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

> **Regla**: Las secciones `<filter><whitelist>` fueron eliminadas en PHPUnit 10. Usa `<source><include>`.
> Los atributos `convertErrorsToExceptions`, `verbose`, `backupStaticAttributes` también están obsoletos.

---

## 2. Atributos PHP 8 — Reemplazo de Docblocks

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MyService::class)]
#[UsesClass(MyRepository::class)]
final class MyServiceTest extends TestCase
{
    #[Test]
    public function it_does_something(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProvider('priceProvider')]
    public function it_calculates_discount(float $price, float $discount, float $expected): void
    {
        $result = round($price - $discount, 2);
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{float, float, float}> */
    public static function priceProvider(): array
    {
        return [
            'precio normal'     => [100.0, 10.0, 90.0],
            'sin descuento'     => [50.0,  0.0,  50.0],
            'descuento total'   => [30.0,  30.0, 0.0],
        ];
    }
}
```

> **Regla**: En PHPUnit 10+ usa `#[Test]` en lugar de `/** @test */`.
> `#[DataProvider]` es obligatorio en PHPUnit 11+ (reemplaza `@dataProvider`).
> Los `DataProvider` deben ser métodos `public static`.

---

## 3. TestCase Base para Paquetes Laravel (Orchestra Testbench)

```php
<?php

declare(strict_types=1);

namespace MyPackage\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use MyPackage\MyServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /** @param \Illuminate\Foundation\Application $app */
    protected function getPackageProviders($app): array
    {
        return [MyServiceProvider::class];
    }

    /** @param \Illuminate\Foundation\Application $app */
    protected function getPackageAliases($app): array
    {
        return [
            'MyFacade' => \MyPackage\Facades\MyFacade::class,
        ];
    }

    /** @param \Illuminate\Foundation\Application $app */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Cargar factories del paquete si existen
    }
}
```

> **Compatibilidad Testbench ↔ Laravel:**
> | Testbench | Laravel |
> |-----------|---------|
> | ^8.0 | 10.x |
> | ^9.0 | 11.x |
> | ^10.0 | 12.x / 13.x |

---

## 4. Mocks y Stubs — Patrones Correctos

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use MyPackage\Contracts\RepositoryInterface;
use MyPackage\Services\UserService;
use MyPackage\DTO\CreateUserDTO;

final class UserServiceTest extends TestCase
{
    private RepositoryInterface&MockObject $repository;
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->service    = new UserService($this->repository);
    }

    #[Test]
    public function it_creates_a_user(): void
    {
        $dto = new CreateUserDTO(name: 'Alice', email: 'alice@example.com');

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(fn($data) => $data['name'] === 'Alice'))
            ->willReturn(['id' => 1, 'name' => 'Alice']);

        $result = $this->service->create($dto);

        $this->assertSame('Alice', $result['name']);
    }

    #[Test]
    public function it_throws_when_repository_fails(): void
    {
        $this->repository
            ->method('create')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->service->create(new CreateUserDTO(name: 'Bob', email: 'bob@test.com'));
    }
}
```

### Tipos de Dobles de Prueba

| Tipo | Método | Uso |
|------|--------|-----|
| **Mock** | `createMock()` | Verifica llamadas y retorna valores |
| **Stub** | `createStub()` | Solo retorna valores, sin verificar llamadas |
| **Spy** | `getMockBuilder()->onlyMethods()` | Verifica interacciones selectivas |
| **Partial Mock** | `getMockBuilder()->setMethods()` | Mock de métodos específicos |

---

## 5. Pruebas de Base de Datos (SQLite in-memory)

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use MyPackage\Tests\TestCase;
use MyPackage\Models\Role;
use MyPackage\Models\Permission;

final class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_role_can_have_permissions(): void
    {
        $role       = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->givePermissionTo($permission);

        $this->assertCount(1, $role->fresh()->permissions);
        $this->assertTrue($role->hasPermissionTo($permission->slug));
    }

    #[Test]
    public function it_stores_role_in_database(): void
    {
        $role = Role::factory()->create(['name' => 'Admin', 'slug' => 'admin']);

        $this->assertDatabaseHas('roles', [
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
    }
}
```

---

## 6. Factories Modernas (Laravel 8+)

```php
<?php

declare(strict_types=1);

namespace MyPackage\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use MyPackage\Models\Role;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->jobTitle();

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'description' => $this->faker->sentence(),
            'special'     => null,
        ];
    }

    public function noAccess(): static
    {
        return $this->state(['special' => 'no-access']);
    }

    public function allAccess(): static
    {
        return $this->state(['special' => 'all-access']);
    }
}
```

> **Regla**: Las factories modernas eliminan la dependencia de `laravel/legacy-factories`.
> Añadir `HasFactory` al modelo y el método `newFactory()` si el namespace no es el estándar.

---

## 7. Excepciones — Verificación Correcta

```php
<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;

final class ExceptionTest extends TestCase
{
    #[Test]
    public function it_throws_permission_not_found(): void
    {
        // ✅ Siempre expectException ANTES de la acción que lanza
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage('Permission not found');
        $this->expectExceptionCode(404);

        $user->hasPermissionTo('i.do.not.exist');
    }

    #[Test]
    public function it_catches_exception_with_closure(): void
    {
        // ✅ Alternativa con assertThrows (PHPUnit 11+)
        $this->assertThrows(
            fn() => $user->hasPermissionTo('i.do.not.exist'),
            PermissionNotFoundException::class,
        );
    }
}
```

---

## 8. Cobertura de Código

```bash
# Generar reporte HTML (requiere Xdebug o PCOV)
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html build/coverage

# Reporte en terminal
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text

# Solo fallar si la cobertura baja del umbral mínimo
XDEBUG_MODE=coverage vendor/bin/phpunit \
    --coverage-clover build/logs/clover.xml \
    --coverage-filter src/
```

```xml
<!-- Añadir umbral mínimo en phpunit.xml -->
<coverage>
    <report>
        <html outputDirectory="build/coverage"/>
        <clover outputFile="build/logs/clover.xml"/>
    </report>
</coverage>
```

---

## 9. Patrones Avanzados

### Test de Middleware (sin HTTP)

```php
#[Test]
public function middleware_blocks_unauthorized_user(): void
{
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    $status = (app()->make(UserHasRole::class))->handle(
        new \Illuminate\Http\Request(),
        fn() => (new \Illuminate\Http\Response())->setContent('<html></html>'),
        'admin'
    )->status();

    $this->assertEquals(401, $status);
}
```

### Test de Blade Directives

```php
#[Test]
public function blade_directive_evaluates_role(): void
{
    $user = User::factory()->create();
    $role = Role::factory()->create(['slug' => 'admin']);
    $user->assignRoles($role);

    $this->actingAs($user);

    $html = view('test-blade-role')->render();

    $this->assertStringContainsString('has admin role', trim($html));
}
```

---

## 10. Checklist de Buenas Prácticas

### Estructura
- [ ] `declare(strict_types=1)` en **todos** los archivos de test
- [ ] Clases de test `final` (no se deben extender)
- [ ] Un concepto por test — un test no debe verificar más de una cosa
- [ ] Nombres descriptivos: `it_does_X_when_Y()` en lugar de `test1()`
- [ ] `setUp()` solo para inicializar estado — sin asserts

### PHPUnit 10+
- [ ] `#[Test]` en lugar de `/** @test */`
- [ ] `#[DataProvider('methodName')]` — DataProviders son `public static`
- [ ] `#[CoversClass]` y `#[UsesClass]` para reportes de cobertura precisos
- [ ] `expectException()` ANTES de la acción que lanza
- [ ] `<source><include>` en `phpunit.xml` (no `<filter><whitelist>`)

### Calidad
- [ ] No dejar `var_dump()`, `dd()`, `dump()` en tests
- [ ] `withoutExceptionHandling()` solo en tests HTTP donde esperas respuestas de error
- [ ] `RefreshDatabase` para tests que modifican la base de datos
- [ ] Factories modernas (Laravel 8+) en lugar de legacy `$factory->define()`
- [ ] Cobertura ≥ 80% en código de negocio
