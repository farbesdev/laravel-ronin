# Laravel Ronin
[![Source](https://img.shields.io/badge/source-farbesdev/laravel--ronin-blue.svg?style=flat-square)](https://github.com/farbesdev/laravel-ronin)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

> [!NOTE]
> **Origin Notice**: This package is an independent fork, completely redesigned and modernized, based on the discontinued [caffeinated/shinobi](https://github.com/caffeinated/shinobi) package created by Shea Lewis. We thank the original author for laying the groundwork for this system.

A simple, lightweight, and highly efficient role-based permission system for Laravel's native Authorization Gate. Fully optimized for high-concurrency environments using hybrid in-memory and Redis caching.

- Each user can have zero or more permissions.
- Each user can have zero or more roles.
- Each role can have zero or more permissions.
- Each role can have one of two special flags: `all-access` (grants all access) and `no-access` (denies all permissions).
- **High-Performance Caching**: Powered by request-level local memory caching and granular keys in Redis.

---

## Installation

Install the package via Composer:

```bash
composer require farbesdev/laravel-ronin
```

### 1. Publish Configuration
To publish the configuration file, run:

```bash
php artisan vendor:publish --provider="Ronin\ShinobiServiceProvider" --tag="config"
```

This will create a `config/ronin.php` file in your application.

### 2. Run Migrations
Run the migrations to create the roles, permissions, and corresponding pivot tables:

```bash
php artisan migrate
```

---

## Configuration

In `config/ronin.php`, you can customize your caching strategy to suit your scalability requirements:

```php
'cache' => [
    // Enable or disable cross-request caching (e.g., using Redis)
    'enabled' => env('RONIN_CACHE_ENABLED', false),

    // Enable local in-memory caching for the current HTTP request
    'request_memory' => true,

    // Enable granular cache per user/role instead of caching the entire permissions table
    'granular' => true,

    // Cache TTL in seconds (defaults to 24 hours)
    'length' => 86400,

    // Prefix used for cache keys in Redis
    'prefix' => 'ronin',
],
```

---

## Cache Architecture

Laravel Ronin includes a hybrid caching mechanism:

1. **Request-Level Cache**: Stores role/permission validation results in static memory for the current HTTP request. Even if the persistent cache is disabled, this ensures that multiple checks for the same user execute exactly **zero database queries**.
2. **Granular Cache in Redis**: Instead of saving the entire permissions database (which degrades performance as data volume grows), Ronin segments the keys:
   - `ronin:user:{id}:roles` -> Stores user roles.
   - `ronin:user:{id}:permissions` -> Stores direct user permissions.
   - `ronin:role:{id}:permissions` -> Stores role permissions.
3. **Smart Invalidation**: When modifying relationships via `assignRoles`, `removeRoles`, `givePermissionTo`, `syncPermissions`, etc., only the specific affected keys are invalidated.

---

## Usage

### Model Configuration
Add the `HasRolesAndPermissions` trait to your `User` model:

```php
use Ronin\Concerns\HasRolesAndPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

### Assigning Roles and Permissions

```php
// Assigning roles
$user->assignRoles('admin');
$user->removeRoles('admin');
$user->syncRoles(['editor', 'moderator']);

// Granting permissions
$user->givePermissionTo('edit.posts');
$user->revokePermissionTo('edit.posts');
$user->syncPermissions(['edit.posts', 'delete.posts']);
```

### Permission Validation

Use Laravel's native Gate or the model's methods:

```php
// Using native Gates
if (Gate::allows('edit.posts')) {
    // ...
}

// User model methods
if ($user->hasPermissionTo('edit.posts')) {
    // ...
}

if ($user->hasRole('admin')) {
    // ...
}
```

### Blade Directives

```html
@can('edit.posts')
    <!-- User has the permission -->
@endcan

@role('admin')
    <!-- User has the admin role -->
@endrole

@anyrole('editor', 'moderator')
    <!-- User has at least one of these roles -->
@endanyrole

@allroles('editor', 'moderator')
    <!-- User has all of these roles -->
@endallroles
```

### Middleware Protection

You can protect your routes with the included middlewares:

```php
Route::group(['middleware' => ['role:admin']], function () {
    // Routes protected by role
});

Route::group(['middleware' => ['permission:edit.posts']], function () {
    // Routes protected by permission
});
```

---

## Testing

Run the test suite with PHPUnit:

```bash
composer test
```

## License

This package is open-source software licensed under the [MIT License](LICENSE.md).
