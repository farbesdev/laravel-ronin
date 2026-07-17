---
name: laravel
description: >
  Experto en Laravel 13 y buenas prácticas. Usar cuando se construyan controladores, servicios,
  repositorios, modelos Eloquent, migraciones, jobs, eventos, listeners, providers, policies,
  Form Requests, recursos API, pruebas con Pest o cualquier elemento del framework Laravel.
  Activa en "Laravel", "Eloquent", "artisan", "middleware Laravel", "provider" o "Form Request".
---

# Laravel 13 — Experto y Buenas Prácticas

Perfil de **Arquitecto Laravel** con dominio total del framework en su versión 13, aplicando
patrones de diseño enterprise, arquitectura modular con `Nwidart/laravel-modules`, Eloquent
avanzado y pruebas automatizadas con Pest.

---

## 1. Estructura de un Módulo (`Nwidart/laravel-modules`)

```text
Modules/
└── Security/
    ├── Actions/           # Casos de uso — una acción, una responsabilidad
    │   └── CreateRoleAction.php
    ├── DTOs/              # Objetos de transferencia de datos (readonly)
    │   └── CreateRoleDTO.php
    ├── Http/
    │   ├── Controllers/   # Controladores delgados (sin lógica de negocio)
    │   │   └── RoleController.php
    │   ├── Requests/      # Form Requests con validación y autorización
    │   │   ├── StoreRoleRequest.php
    │   │   └── UpdateRoleRequest.php
    │   └── Resources/     # API Resources para transformación de respuestas
    │       └── RoleResource.php
    ├── Models/
    │   └── Role.php
    ├── Policies/
    │   └── RolePolicy.php
    ├── Repositories/
    │   ├── RoleRepositoryInterface.php
    │   └── EloquentRoleRepository.php
    ├── Services/
    │   └── RoleService.php
    ├── Database/
    │   ├── Migrations/
    │   ├── Seeders/
    │   └── Factories/
    ├── Routes/
    │   └── api.php
    └── Providers/
        └── SecurityServiceProvider.php
```

---

## 2. DTO Tipado (readonly)

```php
<?php

declare(strict_types=1);

namespace Modules\Security\DTOs;

use Modules\Security\Models\Role;

final readonly class CreateRoleDTO
{
    public function __construct(
        public string $name,
        public string $guard,
        public array  $permissions,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name:        $validated['name'],
            guard:       $validated['guard'] ?? 'web',
            permissions: $validated['permissions'] ?? [],
        );
    }
}
```

---

## 3. Form Request — Validación y Autorización

```php
<?php

declare(strict_types=1);

namespace Modules\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \Modules\Security\Models\Role::class) ?? false;
    }

    /** @return array<string, list<string|\Illuminate\Contracts\Validation\Rule>> */
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'min:2', 'max:80', 'unique:roles,name'],
            'guard'         => ['sometimes', 'string', 'in:web,api'],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un rol con ese nombre.',
        ];
    }
}
```

---

## 4. Controlador Delgado

```php
<?php

declare(strict_types=1);

namespace Modules\Security\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Security\Actions\CreateRoleAction;
use Modules\Security\DTOs\CreateRoleDTO;
use Modules\Security\Http\Requests\StoreRoleRequest;
use Modules\Security\Http\Resources\RoleResource;
use Modules\Security\Repositories\RoleRepositoryInterface;

final class RoleController
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
        private readonly CreateRoleAction        $createAction,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $roles = $this->repository->paginate(perPage: 15);

        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $dto  = CreateRoleDTO::fromRequest($request->validated());
        $role = $this->createAction->execute($dto);

        return RoleResource::make($role)
            ->response()
            ->setStatusCode(201);
    }
}
```

---

## 5. Action — Caso de Uso

```php
<?php

declare(strict_types=1);

namespace Modules\Security\Actions;

use Modules\Security\DTOs\CreateRoleDTO;
use Modules\Security\Models\Role;
use Modules\Security\Repositories\RoleRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CreateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(CreateRoleDTO $dto): Role
    {
        return DB::transaction(function () use ($dto): Role {
            $role = $this->repository->create([
                'name'  => $dto->name,
                'guard' => $dto->guard,
            ]);

            if (!empty($dto->permissions)) {
                $role->syncPermissions($dto->permissions);
            }

            return $role->refresh();
        });
    }
}
```

---

## 6. API Resource — Transformación de Respuesta

```php
<?php

declare(strict_types=1);

namespace Modules\Security\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Security\Models\Role;

/**
 * @mixin Role
 */
final class RoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'guard'       => $this->guard,
            'permissions' => $this->permissions->pluck('name'),
            'createdAt'   => $this->created_at?->toISOString(),
            'updatedAt'   => $this->updated_at?->toISOString(),
        ];
    }
}
```

---

## 7. Pruebas con Pest

```php
<?php

declare(strict_types=1);

use Modules\Security\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('RoleController', function () {

    beforeEach(function () {
        $this->admin = \App\Models\User::factory()->create();
        $this->admin->givePermissionTo('create-roles');
        $this->actingAs($this->admin, 'sanctum');
    });

    it('lista roles paginados', function () {
        Role::factory()->count(5)->create();

        $this->getJson(route('api.security.roles.index'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'guard']], 'meta']);
    });

    it('crea un rol válido', function () {
        $payload = ['name' => 'Editor', 'guard' => 'api'];

        $this->postJson(route('api.security.roles.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Editor');

        $this->assertDatabaseHas('roles', ['name' => 'Editor']);
    });

    it('falla si el nombre ya existe', function () {
        Role::factory()->create(['name' => 'Admin']);

        $this->postJson(route('api.security.roles.store'), ['name' => 'Admin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});
```

---

## 8. Checklist de Buenas Prácticas

- [ ] `declare(strict_types=1)` en todos los archivos PHP
- [ ] Controladores delgados — lógica en Actions/Services
- [ ] Form Requests para validación y autorización
- [ ] DTOs `readonly` para transferencia de datos
- [ ] API Resources para todas las respuestas
- [ ] Inyección de dependencias en constructores (nunca `app()->make()` en producción)
- [ ] Transacciones DB en operaciones compuestas
- [ ] Pruebas Pest para cada endpoint
- [ ] Repositorios con interfaz + implementación Eloquent
