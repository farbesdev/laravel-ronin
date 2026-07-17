---
name: composer-packages
description: >
  Experto en creación y publicación de paquetes Composer para PHP. Usar cuando se diseñe,
  estructure o publique un paquete Composer, se configure composer.json, se implementen
  Service Providers de Laravel, se gestionen dependencias semánticas (SemVer), se publiquen
  en Packagist, se configuren autoloading PSR-4, se escriban pruebas para paquetes o se
  integre CI/CD para paquetes PHP. Activa en "paquete Composer", "Packagist", "composer.json",
  "Service Provider", "autoload PSR-4", "publicar paquete" o "librería PHP".
metadata:
  version: "1.0.0"
  domain: tooling
  triggers: Composer, Packagist, paquete PHP, composer.json, Service Provider, PSR-4, autoload, librería, SemVer
  role: specialist
  scope: implementation
  output-format: code
  related-skills: laravel, php, php-best-practices
---

# Composer Packages — Experto y Buenas Prácticas

Perfil de **Arquitecto de Paquetes PHP** con dominio total en diseño, estructuración, testing
y publicación de paquetes Composer reutilizables, integrables con Laravel y cualquier framework
PSR-compatible.

---

## 1. Estructura Canónica de un Paquete

```text
mi-paquete/
├── src/                        # Código fuente principal (PSR-4)
│   ├── Contracts/              # Interfaces públicas del paquete
│   │   └── MyServiceInterface.php
│   ├── Exceptions/             # Excepciones tipadas del paquete
│   │   └── MyPackageException.php
│   ├── Facades/                # Facades de Laravel (opcional)
│   │   └── MyService.php
│   ├── Http/                   # Controladores / Middleware (si aplica)
│   ├── Commands/               # Artisan commands (si aplica)
│   ├── MyService.php           # Clase principal del paquete
│   └── MyServiceProvider.php  # Service Provider de Laravel
├── config/
│   └── my-package.php         # Configuración publicable
├── database/
│   ├── migrations/            # Migraciones publicables
│   └── factories/
├── resources/
│   └── views/                 # Vistas publicables (si aplica)
├── routes/
│   └── web.php                # Rutas del paquete (si aplica)
├── tests/
│   ├── Unit/
│   ├── Feature/
│   └── TestCase.php           # Clase base de pruebas
├── .github/
│   └── workflows/
│       └── tests.yml          # CI/CD con GitHub Actions
├── composer.json              # Manifiesto del paquete
├── phpstan.neon               # Análisis estático nivel máximo
├── phpunit.xml                # Configuración de pruebas
├── .gitignore
├── CHANGELOG.md
├── LICENSE
└── README.md
```

---

## 2. `composer.json` — Manifiesto Completo

```json
{
    "name": "vendor/mi-paquete",
    "description": "Descripción concisa del paquete en una línea.",
    "type": "library",
    "license": "MIT",
    "keywords": ["laravel", "php", "mi-paquete"],
    "homepage": "https://github.com/vendor/mi-paquete",
    "authors": [
        {
            "name": "Tu Nombre",
            "email": "tu@email.com",
            "homepage": "https://tudominio.com",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.2"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^1.12",
        "laravel/pint": "^1.18"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\MiPaquete\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Vendor\\MiPaquete\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Vendor\\MiPaquete\\MyServiceProvider"
            ],
            "aliases": {
                "MyService": "Vendor\\MiPaquete\\Facades\\MyService"
            }
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "php-http/discovery": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "scripts": {
        "test": "vendor/bin/phpunit",
        "analyse": "vendor/bin/phpstan analyse --level=9",
        "format": "vendor/bin/pint",
        "ci": ["@format", "@analyse", "@test"]
    }
}
```

> **Regla**: `require` solo las dependencias reales de runtime. Todo lo de desarrollo va en `require-dev`.

---

## 3. Service Provider — Integración con Laravel

```php
<?php

declare(strict_types=1);

namespace Vendor\MiPaquete;

use Illuminate\Support\ServiceProvider;
use Vendor\MiPaquete\Contracts\MyServiceInterface;

final class MyServiceProvider extends ServiceProvider
{
    /**
     * Registrar bindings en el contenedor.
     * NO disparar eventos, escuchar eventos ni acceder a nada aquí.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/my-package.php',
            'my-package',
        );

        $this->app->singleton(
            MyServiceInterface::class,
            fn ($app) => new MyService(
                config: $app['config']->get('my-package'),
            ),
        );
    }

    /**
     * Bootstrapping: rutas, vistas, migraciones, eventos.
     * Solo se ejecuta si el paquete es usado por la app.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
            $this->publishMigrations();
            $this->loadCommands();
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mi-paquete');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/my-package.php' => config_path('my-package.php'),
        ], 'my-package-config');
    }

    private function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'my-package-migrations');
    }

    private function loadCommands(): void
    {
        $this->commands([
            // Commands\MyCommand::class,
        ]);
    }
}
```

---

## 4. Facade — Acceso Estático Limpio

```php
<?php

declare(strict_types=1);

namespace Vendor\MiPaquete\Facades;

use Illuminate\Support\Facades\Facade;
use Vendor\MiPaquete\Contracts\MyServiceInterface;

/**
 * @method static string process(string $input)
 * @method static array  getData(): array
 *
 * @see \Vendor\MiPaquete\MyService
 */
final class MyService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MyServiceInterface::class;
    }
}
```

---

## 5. Contrato (Interface) — Principio de Inversión de Dependencias

```php
<?php

declare(strict_types=1);

namespace Vendor\MiPaquete\Contracts;

interface MyServiceInterface
{
    public function process(string $input): string;

    /** @return array<string, mixed> */
    public function getData(): array;
}
```

---

## 6. Implementación del Servicio Principal

```php
<?php

declare(strict_types=1);

namespace Vendor\MiPaquete;

use Vendor\MiPaquete\Contracts\MyServiceInterface;
use Vendor\MiPaquete\Exceptions\MyPackageException;

final class MyService implements MyServiceInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
    ) {}

    public function process(string $input): string
    {
        if (empty($input)) {
            throw new MyPackageException('El input no puede estar vacío.');
        }

        // Lógica principal del paquete
        return strtoupper($input);
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        return $this->config;
    }
}
```

---

## 7. TestCase Base con Orchestra Testbench

```php
<?php

declare(strict_types=1);

namespace Vendor\MiPaquete\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Vendor\MiPaquete\MyServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MyServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'MyService' => \Vendor\MiPaquete\Facades\MyService::class,
        ];
    }

    /** @param \Illuminate\Foundation\Application $app */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('my-package.option_one', 'test_value');
    }
}
```

---

## 8. Prueba de Unidad con Pest

```php
<?php

declare(strict_types=1);

use Vendor\MiPaquete\MyService;
use Vendor\MiPaquete\Exceptions\MyPackageException;

covers(MyService::class);

describe('MyService', function () {

    beforeEach(function () {
        $this->service = new MyService(config: ['option_one' => 'value']);
    });

    it('procesa texto en mayúsculas', function () {
        $result = $this->service->process('hola mundo');

        expect($result)->toBe('HOLA MUNDO');
    });

    it('lanza excepción con input vacío', function () {
        expect(fn () => $this->service->process(''))
            ->toThrow(MyPackageException::class, 'El input no puede estar vacío.');
    });

    it('retorna la configuración', function () {
        $data = $this->service->getData();

        expect($data)->toHaveKey('option_one', 'value');
    });
});
```

---

## 9. Prueba de Integración con Laravel (Feature)

```php
<?php

declare(strict_types=1);

use Vendor\MiPaquete\Facades\MyService;
use Vendor\MiPaquete\Tests\TestCase;

uses(TestCase::class);

it('resuelve el servicio desde el contenedor', function () {
    $service = app(\Vendor\MiPaquete\Contracts\MyServiceInterface::class);

    expect($service)->toBeInstanceOf(\Vendor\MiPaquete\MyService::class);
});

it('la facade procesa correctamente', function () {
    $result = MyService::process('laravel');

    expect($result)->toBe('LARAVEL');
});
```

---

## 10. CI/CD con GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      matrix:
        php: [8.2, 8.3, 8.4]
        laravel: [10.*, 11.*, 12.*]
        stability: [prefer-stable]

    name: PHP ${{ matrix.php }} - Laravel ${{ matrix.laravel }}

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, pdo, sqlite, pdo_sqlite
          coverage: none

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" --no-update
          composer update --${{ matrix.stability }} --prefer-dist --no-interaction

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --level=9

      - name: Run tests
        run: vendor/bin/pest --ci
```

---

## 11. Versionado Semántico (SemVer)

| Versión | Cambio | Ejemplo |
|---------|--------|---------|
| `PATCH` (x.x.**Z**) | Corrección de bug, sin romper API | `1.0.1` |
| `MINOR` (x.**Y**.0) | Nueva funcionalidad retrocompatible | `1.1.0` |
| `MAJOR` (**X**.0.0) | Cambio que rompe la API pública | `2.0.0` |

```bash
# Flujo de release con tags Git
git tag -a v1.2.0 -m "feat: soporte para PHP 8.4"
git push origin v1.2.0
```

**Constraints en `composer.json` de proyectos que consumen el paquete:**

```json
"vendor/mi-paquete": "^1.2"   // ≥1.2.0 y <2.0.0 (recomendado)
"vendor/mi-paquete": "~1.2.0" // ≥1.2.0 y <1.3.0 (conservador)
"vendor/mi-paquete": "^2.0"   // ≥2.0.0 y <3.0.0
```

---

## 12. Publicación en Packagist

```bash
# 1. Subir el paquete a GitHub (repositorio público o privado)
git init && git add . && git commit -m "feat: initial release"
git remote add origin https://github.com/vendor/mi-paquete.git
git push -u origin main

# 2. Ir a https://packagist.org y registrar el repositorio

# 3. Configurar webhook de GitHub para auto-actualización
# Settings → Webhooks → Add webhook
# Payload URL: https://packagist.org/api/github?username=vendor
# Content type: application/json
# Secret: (API token de Packagist)
# Trigger: push events
```

### Para paquetes privados (Satis o Composer Repository)

```json
// composer.json del proyecto consumidor
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/empresa/paquete-privado"
        }
    ]
}
```

---

## 13. Archivo de Configuración Publicable

```php
<?php

// config/my-package.php
return [
    /*
    |--------------------------------------------------------------------------
    | Opción principal
    |--------------------------------------------------------------------------
    | Descripción detallada de qué hace esta opción y sus valores posibles.
    */
    'option_one' => env('MY_PACKAGE_OPTION_ONE', 'default_value'),

    /*
    |--------------------------------------------------------------------------
    | Tiempo de caché en segundos
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => env('MY_PACKAGE_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Driver activo
    |--------------------------------------------------------------------------
    | Opciones: 'default', 'custom'
    */
    'driver' => env('MY_PACKAGE_DRIVER', 'default'),
];
```

---

## 14. Excepción Tipada del Paquete

```php
<?php

declare(strict_types=1);

namespace Vendor\MiPaquete\Exceptions;

use RuntimeException;

final class MyPackageException extends RuntimeException
{
    public static function invalidInput(string $input): self
    {
        return new self("Input inválido proporcionado: '{$input}'.");
    }

    public static function configurationMissing(string $key): self
    {
        return new self("Falta la configuración requerida: '{$key}'.");
    }
}
```

---

## 15. Checklist de Buenas Prácticas

### Estructura y Código
- [ ] `declare(strict_types=1)` en **todos** los archivos PHP
- [ ] Namespace raíz coincide exactamente con `autoload.psr-4` en `composer.json`
- [ ] Interfaces en `Contracts/` para cada servicio público
- [ ] Excepciones propias en `Exceptions/` (nunca lanzar `\Exception` genérico)
- [ ] PHPStan nivel 9 pasa sin errores
- [ ] Clases `final` por defecto; solo abrir herencia si es necesario

### Service Provider
- [ ] `register()` solo hace binding en el contenedor (sin efectos secundarios)
- [ ] `boot()` envuelto en `runningInConsole()` cuando registra comandos/publicaciones
- [ ] Config mergeada con `mergeConfigFrom()` (no sobrescribe config del usuario)
- [ ] Usa grupos de publicación con etiquetas descriptivas

### Testing
- [ ] `TestCase` extiende `Orchestra\Testbench\TestCase`
- [ ] Cobertura ≥ 80% (unitaria + integración)
- [ ] Prueba que el Service Provider registra los bindings correctamente
- [ ] Prueba los casos de error y las excepciones tipadas

### Publicación y Versionado
- [ ] SemVer estricto con `CHANGELOG.md` actualizado en cada release
- [ ] Tags Git para cada versión (`v1.0.0`)
- [ ] Matriz de compatibilidad declarada en `composer.json` (`require.php`)
- [ ] README con instalación, configuración y ejemplos de uso claros
- [ ] Licencia declarada (`LICENSE` y `"license"` en `composer.json`)
- [ ] Webhook de Packagist configurado para auto-actualizar en push
