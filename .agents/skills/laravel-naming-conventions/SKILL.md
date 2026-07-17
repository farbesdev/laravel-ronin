# 🛠️ Buenas Prácticas y Convenciones de Nombrado para Laravel 13 y PHP 8.5

Este skill establece las reglas de nomenclatura y estilo de codificación en el backend (Laravel 13 y PHP 8.5) para garantizar consistencia, legibilidad y un código limpio basado en estándares de la comunidad.

---

## 🐘 Estándares PHP 8.5

1. **Tipado Estricto (Strict Types):**
   Todos los archivos PHP deben comenzar estrictamente con:
   ```php
   <?php

   declare(strict_types=1);
   ```

2. **Clases y Estructuras:**
   - **PascalCase** para Clases, Interfaces, Traits y Enums (`UserService`, `HasExternalId`, `ModuleEnum`).
   - Usar `final` en clases que no necesiten heredarse (ej. Servicios, Controladores, Form Requests, Repositorios).
   - Tipado estricto en todos los parámetros, retornos de funciones y propiedades.

3. **Propiedades y Variables:**
   - **camelCase** para propiedades de clases y variables locales (`$externalId`, `$activeOrgsCount`).
   - Usar visibilidad asimétrica (`public readonly` o property hooks) cuando sea apropiado.

4. **Métodos y Funciones:**
   - **camelCase** (`listPaginated`, `findByExternalId`, `countByStatus`).

5. **Constantes y Casos de Enums:**
   - **UPPER_SNAKE_CASE** para constantes tradicionales de clase (`public const string EXTERNAL_ID_PREFIX = 'dep'`).
   - **UPPER_SNAKE_CASE** o **camelCase** para casos de Enums según el estándar del proyecto (`case CORE_LOCATION = 'loc'`).

---

## 🚀 Convenciones de Nivel de Laravel 13

### 1. Modelos y Entidades
- **PascalCase** y en singular (`Country`, `Department`, `User`).
- Ubicación: Dentro de su contexto bajo la carpeta `Entities/` (`Modules/Core/contexts/Location/Entities/Department.php`).

### 2. Base de Datos
- **Tablas:** Plural y en **snake_case** (`users`, `loc_departments`, `mar_countries`).
- **Columnas:** **snake_case** (`external_id`, `phone_prefix`, `is_active`).
- **Llaves Primarias:** Tradicional `id` (autoincrement) o `external_id` expuesto en la API.
- **Llaves Foráneas:** Singular del modelo relacionado + `_id` (`user_id`, `organization_id`).
- **Tablas Pivote:** Singular de los nombres de los modelos en orden alfabético (`role_user`).

### 3. Controladores
- **PascalCase** con sufijo `Controller` (`UserController`, `DepartmentController`).
- Para API REST modular, agrupar bajo `Http/Controllers/Api/`.
- Usar controladores de acción única (`__invoke`) cuando sea posible para seguir SRP.

### 4. Form Requests
- **PascalCase** con prefijo de acción (`Store` / `Update`) y sufijo `Request` (`StoreDepartmentRequest`, `UpdateDepartmentRequest`).
- **Organización Obligatoria:** Organizados por carpeta de Entidad bajo `Http/Requests/{Entidad}/`.

### 5. API Resources
- **PascalCase** con sufijo `Resource` (`DepartmentResource`, `UserResource`).
- Ubicación: `Http/Resources/`.

### 6. Repositorios y Servicios
- **Repositorios:** PascalCase con sufijo `Repository` (`DepartmentRepository`).
- **Servicios:** PascalCase con sufijo `Service` (`DepartmentService`).

### 7. Seeders y Factories
- **Seeders:** PascalCase con sufijo `Seeder` (`DepartmentSeeder`).
- **Factories:** PascalCase con sufijo `Factory` (`UserFactory`).

### 8. Rutas
- URLs en **kebab-case** (`core/identity-document-types`, `core/departments`).
- Nombre de la ruta en minúsculas usando notación de puntos (dot notation) y con nombres en plural (`core.departments.index`, `users.selector`).

---

## 📋 Método Estandarizado `listForSelect` (Selector)
Para poblar componentes `<VSelect>` en el frontend, cada dupla `Repository` + `Service` + `Controller` debe exponer el método `listForSelect()` con la siguiente estructura de respuesta:

```json
[
  {
    "id": "external_id_value",
    "name": "Nombre o campo descriptivo",
    "description": "Texto opcional (ej. email) o null"
  }
]
```

### Ejemplo en Repositorio:
```php
public function listForSelect(): array
{
    return Department::where('is_active', true)
        ->orderBy('name')
        ->get(['external_id', 'name'])
        ->map(fn($item) => [
            'id'          => $item->external_id,
            'name'        => $item->name,
            'description' => null,
        ])
        ->toArray();
}
```
