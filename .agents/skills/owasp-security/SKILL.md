---
name: owasp-security
description: >
  Experto en seguridad de aplicaciones web basado en el OWASP Top Ten (2021).
  Usar cuando se implementen controles de acceso, autenticación, autorización,
  validación de inputs, cifrado de datos, gestión de dependencias, logging de
  seguridad, protección contra inyecciones (SQL, LDAP, OS, NoSQL), configuración
  segura de servidores, protección SSRF o se revise el código en busca de
  vulnerabilidades. Incluye flujo de trabajo BDD → TDD → Code Reviewer → Verify
  para garantizar la seguridad en cada ciclo de desarrollo. Activa en "OWASP",
  "seguridad", "vulnerabilidad", "inyección SQL", "XSS", "CSRF", "autenticación",
  "autorización", "broken access control", "criptografía", "dependencias inseguras",
  "logging de seguridad", "SSRF", "BDD seguridad", "TDD seguridad" o "revisión de seguridad".
metadata:
  version: "1.0.0"
  domain: security
  triggers: >
    OWASP, seguridad, vulnerabilidad, inyección SQL, XSS, CSRF, autenticación,
    autorización, broken access control, criptografía, dependencias inseguras,
    logging de seguridad, SSRF, BDD seguridad, TDD seguridad, revisión de seguridad,
    código inseguro, sanitización, validación de inputs
  role: security-specialist
  scope: implementation, testing, review, verification
  output-format: code + checklist + workflow
  related-skills: laravel, php, php-best-practices, phpunit, larastan
---


# OWASP Top Ten — Experto en Seguridad Web para PHP/Laravel

Perfil de **Arquitecto de Seguridad** con dominio total en los 10 riesgos más críticos
de seguridad web según OWASP (edición 2021), su prevención en PHP 8.x/Laravel y la
integración de controles de seguridad dentro del ciclo de desarrollo mediante
**BDD → TDD → Code Review → Verification**.

---

## ⚠️ PRIMER PASO OBLIGATORIO: Identificar el Contexto del Proyecto

Antes de ejecutar cualquier verificación OWASP, determinar el **tipo de proyecto**:

| Tipo | Descripción | Ejemplos |
|------|-------------|---------|
| 📦 **Paquete / Librería** | Código reutilizable consumido por apps. Sin rutas HTTP propias, sin login, sin sesiones. | `laravel-ronin`, `spatie/permission`, middlewares, traits |
| 🌐 **Aplicación Web** | Tiene endpoints HTTP, usuarios, autenticación propia, formularios, sesiones. | Laravel app, API REST, SPA backend |

### Aplicabilidad OWASP por Tipo de Proyecto

| Riesgo OWASP | 📦 Paquete | 🌐 Aplicación | Alcance en Paquete |
|---|---|---|---|
| **A01 Broken Access Control** | ✅ Aplica | ✅ Aplica | Middleware, roles, permisos, flags especiales |
| **A02 Cryptographic Failures** | ⚠️ Solo auditar | ✅ Aplica | Verificar que no usa MD5/SHA1/DES internamente |
| **A03 Injection** | ✅ Aplica | ✅ Aplica | Slugs como input, Eloquent queries, sanitización |
| **A04 Insecure Design** | ✅ Aplica | ✅ Aplica | API design, edge cases, fail-safe defaults |
| **A05 Security Misconfiguration** | ✅ Aplica | ✅ Aplica | Valores por defecto del config publicable |
| **A06 Vulnerable Components** | ✅ Aplica | ✅ Aplica | `composer audit` en dependencias |
| **A07 Auth Failures** | ❌ **NO aplica** | ✅ Aplica | El paquete NO implementa login, sesiones ni contraseñas. Solo lee el **estado** de auth que la app ya estableció. Reencuadrar como: "comportamiento del middleware con guests vs usuarios autenticados" |
| **A08 Integrity Failures** | ✅ Aplica | ✅ Aplica | Cache poisoning, invalidación de permisos en BD |
| **A09 Logging Failures** | ⚠️ Parcial | ✅ Aplica | El paquete despacha **eventos** auditables; la **app** consumidora decide cómo loggearlos |
| **A10 SSRF** | ❌ **NO aplica** | ✅ Aplica | Solo si el paquete hace peticiones HTTP externas |

> [!IMPORTANT]
> **Paquetes Laravel** (`composer require`): NO verificar login, registro, contraseñas, MFA, sesiones, tokens de reset ni rate limiting de autenticación. Esos son responsabilidad de la aplicación consumidora, no del paquete.

---


## WORKFLOW PRINCIPAL: BDD → TDD → Code Review → Verify

Cada funcionalidad o parche de seguridad debe seguir este ciclo estrictamente:

```
┌──────────────────────────────────────────────────────────────────────┐
│                   SECURITY DEVELOPMENT WORKFLOW                      │
│                                                                      │
│  1. BDD           2. TDD            3. CODE REVIEW   4. VERIFY      │
│  ─────────        ──────────        ─────────────     ──────────     │
│  Escenario        Test falla        Checklist         Análisis       │
│  Gherkin    →     (Red) →           OWASP       →     estático +     │
│  (Given-          Implementa        por riesgo         pruebas       │
│  When-Then)       (Green) →                           integrales     │
│                   Refactor                                           │
└──────────────────────────────────────────────────────────────────────┘
```

---

## FASE 1: BDD — Definir el Comportamiento Seguro

Los escenarios BDD definen **qué debe hacer la aplicación** desde la perspectiva de seguridad,
usando el patrón Given-When-Then con Pest.

### Plantilla de escenario de seguridad:

```php
<?php

declare(strict_types=1);

// Feature: [Nombre del riesgo OWASP que se mitiga]
// As a security engineer
// I want to ensure [control de seguridad]
// So that [amenaza mitigada]

describe('[A0X] [Nombre del Riesgo OWASP]', function () {

    // Escenario: acceso no autorizado bloqueado
    it('[A0X] GIVEN usuario sin permiso WHEN intenta acceder THEN recibe 403', function () {
        // Given
        $user = User::factory()->withoutRole('admin')->create();

        // When
        $response = actingAs($user)->get('/admin/dashboard');

        // Then
        $response->assertStatus(403);
        $response->assertForbidden();
    });

    // Escenario: input malicioso rechazado
    it('[A0X] GIVEN input malicioso WHEN se envía al endpoint THEN se rechaza con 422', function () {
        // Given
        $maliciousInput = "<script>alert('xss')</script>";

        // When
        $response = post('/api/resource', ['name' => $maliciousInput]);

        // Then
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });
});
```

---

## FASE 2: TDD — Red → Green → Refactor con Foco en Seguridad

El ciclo TDD para seguridad en Laravel/PHP sigue tres pasos:

```
RED   → Escribe el test de seguridad que FALLA (vulnerabilidad existe)
GREEN → Implementa el control de seguridad mínimo (test pasa)
REFACTOR → Limpia el código sin sacrificar la seguridad
```

### Ejemplo de ciclo TDD para A01 (Broken Access Control):

```php
// 🔴 RED: Test falla porque no hay control de acceso
it('bloquea acceso a recurso ajeno sin autorización', function () {
    $owner   = User::factory()->create();
    $attacker = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create();

    $response = actingAs($attacker)->delete("/api/resources/{$resource->id}");

    $response->assertForbidden(); // FALLA: retorna 200 actualmente
});

// 🟢 GREEN: Implementar Policy en Laravel
// app/Policies/ResourcePolicy.php
class ResourcePolicy
{
    public function delete(User $user, Resource $resource): bool
    {
        return $user->id === $resource->user_id;
    }
}

// 🔵 REFACTOR: Añadir Gate::authorize en el controlador
public function destroy(Resource $resource): JsonResponse
{
    Gate::authorize('delete', $resource);
    $resource->delete();
    return response()->json(['message' => 'Eliminado.']);
}
```

---

## FASE 3: CODE REVIEWER — Checklist por Riesgo OWASP

Para cada Pull Request o revisión de código, aplicar el checklist correspondiente a los
riesgos OWASP identificados en el cambio.

---

## FASE 4: VERIFY — Comandos de Verificación

```bash
# 1. Análisis estático con PHPStan/Larastan
vendor/bin/phpstan analyse --level=9

# 2. Ejecutar toda la suite de pruebas de seguridad
vendor/bin/pest --group=security --coverage

# 3. Verificar dependencias con vulnerabilidades conocidas
composer audit

# 4. Buscar patrones inseguros con grep
grep -rn "eval(" src/
grep -rn "unserialize(" src/
grep -rn "md5(" src/
grep -rn "sha1(" src/
grep -rn "\$_GET\|\$_POST\|\$_REQUEST" src/

# 5. Revisar configuraciones de seguridad de Laravel
php artisan config:show | grep -i "debug\|key\|cipher"
```

---

## A01:2021 — Broken Access Control (Control de Acceso Roto)

**Descripción**: Los usuarios actúan fuera de sus permisos previstos. Es el riesgo #1.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Usar Policies y Gates

// app/Policies/DocumentPolicy.php
final class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->user_id
            || $user->hasRole('admin');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }
}

// app/Http/Controllers/DocumentController.php
final class DocumentController extends Controller
{
    public function show(Document $document): DocumentResource
    {
        // ✅ Lanza 403 si falla la política
        Gate::authorize('view', $document);
        return new DocumentResource($document);
    }

    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        Gate::authorize('update', $document);
        $document->update($request->validated());
        return response()->json(['message' => 'Actualizado.']);
    }
}

// ❌ NUNCA hacer esto:
// if ($request->user_id == $document->user_id) { ... } // Manipulable vía request
// Document::find($request->id)->delete(); // Sin verificación de pertenencia
```

### BDD Test

```php
describe('[A01] Broken Access Control', function () {
    it('GIVEN user sin permiso WHEN accede a documento ajeno THEN retorna 403', function () {
        $owner    = User::factory()->create();
        $attacker = User::factory()->create();
        $doc      = Document::factory()->for($owner)->create();

        actingAs($attacker)->getJson("/api/documents/{$doc->id}")
            ->assertForbidden();
    });

    it('GIVEN admin WHEN accede a cualquier documento THEN retorna 200', function () {
        $admin = User::factory()->admin()->create();
        $doc   = Document::factory()->create();

        actingAs($admin)->getJson("/api/documents/{$doc->id}")
            ->assertOk();
    });

    it('GIVEN IDOR attack WHEN modifica ID en URL THEN retorna 403', function () {
        $user    = User::factory()->create();
        $victim  = User::factory()->create();
        $doc     = Document::factory()->for($victim)->create();

        actingAs($user)->putJson("/api/documents/{$doc->id}", ['title' => 'Hacked'])
            ->assertForbidden();
    });
})->group('security', 'A01');
```

### Code Reviewer Checklist — A01

- [ ] Cada ruta protegida tiene middleware `auth` o `auth:sanctum`
- [ ] Todas las acciones usan `Gate::authorize()` o `$this->authorize()` en el controlador
- [ ] No se confía en IDs enviados por el cliente para operaciones sensibles
- [ ] Las rutas de administración están separadas y protegidas con roles
- [ ] Se usa route model binding con scoping implícito (no `find($id)` manual)
- [ ] No hay rutas de API expuestas sin autenticación que devuelvan datos sensibles

---

## A02:2021 — Cryptographic Failures (Fallos Criptográficos)

**Descripción**: Exposición de datos sensibles por cifrado débil o ausente.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Cifrado y hashing adecuados

// Hashing de contraseñas — SIEMPRE bcrypt/argon2
$hashed = Hash::make($password); // Usa bcrypt por defecto en Laravel
// Verificación
if (Hash::check($plainText, $hashed)) { ... }

// Cifrado de datos sensibles en base de datos
use Illuminate\Database\Eloquent\Casts\Encrypted;
use Illuminate\Database\Eloquent\Casts\EncryptedString;

final class Patient extends Model
{
    protected $casts = [
        'ssn'            => EncryptedString::class, // ✅ Cifrado AES-256-CBC
        'medical_record' => Encrypted::class,
        'birth_date'     => 'date',
    ];
}

// Transmisión segura: forzar HTTPS
// app/Http/Middleware/ForceHttps.php
final class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }
        return $next($request);
    }
}

// ❌ NUNCA usar:
// md5($password), sha1($password)       — Algoritmos rotos para contraseñas
// base64_encode($sensitiveData)         — No es cifrado
// openssl_encrypt($data, 'DES-CBC',...) — DES está roto
// $value = Crypt::encrypt($data);       — Sin problema si se usa correctamente
```

### BDD Test

```php
describe('[A02] Cryptographic Failures', function () {
    it('GIVEN contraseña WHEN se almacena THEN no se guarda en texto plano', function () {
        $user = User::factory()->create(['password' => 'secret123']);
        expect($user->password)->not->toBe('secret123');
        expect(Hash::check('secret123', $user->password))->toBeTrue();
    });

    it('GIVEN dato sensible WHEN se recupera de la BD THEN está cifrado en disco', function () {
        $patient = Patient::factory()->create(['ssn' => '123-45-6789']);
        $rawValue = DB::table('patients')->where('id', $patient->id)->value('ssn');
        expect($rawValue)->not->toBe('123-45-6789'); // Debe estar cifrado en BD
        expect($patient->ssn)->toBe('123-45-6789');  // Desencriptado al leer
    });
})->group('security', 'A02');
```

### Code Reviewer Checklist — A02

- [ ] Las contraseñas usan `Hash::make()` (bcrypt/argon2), nunca MD5/SHA1
- [ ] Los datos sensibles en BD usan el cast `Encrypted` de Laravel
- [ ] `APP_KEY` tiene ≥32 caracteres aleatorios y está en `.env` (nunca en código)
- [ ] La app fuerza HTTPS en producción (`FORCE_HTTPS=true`)
- [ ] No se transmiten datos sensibles en URLs/query strings
- [ ] Cookies tienen los flags `Secure`, `HttpOnly` y `SameSite=Strict`
- [ ] `SESSION_SECURE_COOKIE=true` en producción

---

## A03:2021 — Injection (Inyección)

**Descripción**: SQL, LDAP, OS, XSS y otras inyecciones por datos no sanitizados.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Usar el ORM Eloquent o consultas parametrizadas

// SQL — Eloquent (seguro por defecto)
$users = User::where('email', $email)->get();

// SQL — Query Builder parametrizado
$users = DB::table('users')->where('email', '=', $email)->get();

// SQL — Raw con bindings (NUNCA concatenar)
$users = DB::select('SELECT * FROM users WHERE email = ?', [$email]);
// O con named bindings:
$users = DB::select('SELECT * FROM users WHERE email = :email', ['email' => $email]);

// XSS — Blade escapa automáticamente con {{ }}
// ✅ {{ $user->name }}    — Escapado automático (seguro)
// ❌ {!! $user->name !!} — SIN escape (solo para HTML de confianza)

// XSS — Sanitizar HTML si se permite formato (con HTMLPurifier)
use HTMLPurifier;
$safeHtml = (new HTMLPurifier())->purify($untrustedHtml);

// OS Injection — Nunca ejecutar comandos con input de usuario
// ❌ exec("convert " . $userInput . " output.jpg");
// ✅ Usar escapeshellarg()
$safeFilename = escapeshellarg($userInput);
exec("convert {$safeFilename} output.jpg");
// ✅ Mejor: usar librerías PHP (Intervention Image, etc.)

// ❌ NUNCA hacer esto:
// DB::select("SELECT * FROM users WHERE name = '$name'"); // SQL Injection
// echo $_GET['message']; // XSS directo
```

### Form Request para validación estricta

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'max:2000',
                'not_regex:/<script[\s\S]*?>[\s\S]*?<\/script>/i', // Anti-XSS básico
            ],
            'post_id' => ['required', 'integer', 'exists:posts,id'],
        ];
    }
}
```

### BDD Test

```php
describe('[A03] Injection', function () {
    it('GIVEN payload SQL WHEN busca usuario THEN no expone datos ajenos', function () {
        User::factory()->create(['email' => 'victim@example.com']);

        $response = getJson("/api/users?email=' OR '1'='1");
        $response->assertStatus(422); // Debe fallar en validación
    });

    it('GIVEN script XSS WHEN se guarda comentario THEN se escapa al renderizar', function () {
        $user    = User::factory()->create();
        $payload = "<script>alert('XSS')</script>";

        actingAs($user)
            ->postJson('/api/comments', ['content' => $payload])
            ->assertStatus(422); // Rechazado por validación
    });

    it('GIVEN comentario válido WHEN se renderiza THEN los caracteres especiales están escapados', function () {
        $comment = Comment::factory()->create(['content' => '<b>bold</b>']);

        // En Blade: {{ $comment->content }} → &lt;b&gt;bold&lt;/b&gt;
        expect(e($comment->content))->toBe('&lt;b&gt;bold&lt;/b&gt;');
    });
})->group('security', 'A03');
```

### Code Reviewer Checklist — A03

- [ ] Todas las consultas usan Eloquent o Query Builder con bindings (nunca concatenación)
- [ ] Todo input de usuario pasa por un `FormRequest` con reglas de validación
- [ ] Las vistas Blade usan `{{ }}` (escapado) y no `{!! !!}` salvo para HTML de confianza explícita
- [ ] Los comandos de sistema (si existen) usan `escapeshellarg()` o librerías PHP
- [ ] No se usa `eval()` en ningún contexto
- [ ] Los inputs de tipo archivo validan extensión + mime type + tamaño

---

## A04:2021 — Insecure Design (Diseño Inseguro)

**Descripción**: Ausencia de patrones de diseño seguros desde la arquitectura.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Diseño seguro desde el comienzo

// Rate Limiting — Proteger endpoints sensibles
// routes/api.php
Route::middleware(['throttle:5,1'])->group(function () { // 5 req/min
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
});

// Para OTPs y acciones críticas: throttle más estricto
Route::middleware(['throttle:3,60'])->group(function () { // 3 intentos/hora
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
});

// Separación de lógica de negocio en servicios testeables
final class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenRepository $tokens,
    ) {}

    public function requestReset(string $email): void
    {
        // ✅ Siempre responde igual (no revela si el email existe)
        $token = $this->tokens->generate();

        if ($user = $this->users->findByEmail($email)) {
            $this->tokens->store($user->id, $token);
            Mail::to($user)->send(new PasswordResetMail($token));
        }
        // Sin else: respuesta siempre idéntica para prevenir user enumeration
    }
}
```

### BDD Test

```php
describe('[A04] Insecure Design', function () {
    it('GIVEN email inexistente WHEN solicita reset THEN responde igual que si existiera', function () {
        $responseExistent    = post('/auth/forgot-password', ['email' => 'real@example.com']);
        $responseNonExistent = post('/auth/forgot-password', ['email' => 'fake@nowhere.com']);

        // Previene user enumeration: misma respuesta, mismo status
        expect($responseExistent->status())->toBe($responseNonExistent->status());
    });

    it('GIVEN 6 intentos de login WHEN se excede el límite THEN retorna 429', function () {
        foreach (range(1, 5) as $attempt) {
            post('/auth/login', ['email' => 'user@test.com', 'password' => 'wrong']);
        }
        $response = post('/auth/login', ['email' => 'user@test.com', 'password' => 'wrong']);
        $response->assertStatus(429);
    });
})->group('security', 'A04');
```

### Code Reviewer Checklist — A04

- [ ] Los endpoints sensibles (login, registro, reset) tienen rate limiting
- [ ] Los mensajes de error no revelan si un recurso existe o no (user enumeration)
- [ ] Los flujos críticos están documentados con consideraciones de seguridad
- [ ] Los tokens de un solo uso (OTP, reset) se invalidan tras ser usados
- [ ] Los tokens temporales tienen un TTL corto y explícito (15-30 min)

---

## A05:2021 — Security Misconfiguration (Mala Configuración de Seguridad)

**Descripción**: Configuraciones por defecto inseguras, verbose errors, puertos abiertos.

### Mitigación en Laravel

```php
<?php
// ✅ Configuraciones críticas de seguridad en .env (producción)

// .env.production (plantilla de valores correctos)
// APP_DEBUG=false           ← CRÍTICO: nunca true en producción
// APP_ENV=production
// FORCE_HTTPS=true
// SESSION_SECURE_COOKIE=true
// SESSION_HTTP_ONLY=true    ← Inaccessible via JS
// SESSION_SAME_SITE=strict
// LOG_LEVEL=error           ← No exponer stack traces al exterior

// config/session.php — Verificar en código
// 'secure'    => env('SESSION_SECURE_COOKIE', false),
// 'http_only' => true,
// 'same_site' => 'strict',

// Headers de seguridad — Middleware personalizado
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'"
        );
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
```

### BDD Test

```php
describe('[A05] Security Misconfiguration', function () {
    it('GIVEN modo debug WHEN ocurre excepción THEN no expone stack trace al cliente', function () {
        $response = get('/trigger-error-for-testing');
        $response->assertStatus(500);
        $response->assertDontSee('vendor/laravel');
        $response->assertDontSee('Symfony\Component');
    });

    it('GIVEN cualquier respuesta WHEN se revisan headers THEN contiene cabeceras de seguridad', function () {
        $response = get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    });
})->group('security', 'A05');
```

### Code Reviewer Checklist — A05

- [ ] `APP_DEBUG=false` en producción
- [ ] No existe ningún archivo `.env` commiteado en el repositorio
- [ ] Las migraciones no exponen datos de ejemplo en producción
- [ ] Los headers de seguridad (CSP, X-Frame-Options, etc.) están presentes
- [ ] Las dependencias de desarrollo no están instaladas en producción
- [ ] Los directorios sensibles (`.git`, `vendor`, `storage`) no son accesibles via web
- [ ] Se usa un handler de errores personalizado que no filtra detalles internos

---

## A06:2021 — Vulnerable and Outdated Components (Componentes Vulnerables)

**Descripción**: Usar librerías/frameworks con CVEs conocidos.

### Mitigación en Laravel

```bash
# ✅ Verificar vulnerabilidades en dependencias PHP
composer audit

# ✅ Actualizar dependencias con seguridad
composer update --prefer-stable

# ✅ Automatizar en CI/CD (GitHub Actions)
# .github/workflows/security.yml
```

```yaml
# .github/workflows/security.yml
name: Security Audit

on:
  schedule:
    - cron: '0 8 * * 1'  # Lunes 8am
  push:
    paths:
      - 'composer.lock'

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer audit
      - run: composer outdated --direct
```

### BDD Test

```php
describe('[A06] Vulnerable Components', function () {
    it('GIVEN composer.lock WHEN se auditan dependencias THEN no hay CVEs críticos', function () {
        $output = shell_exec('composer audit --format=json 2>&1');
        $result = json_decode($output, true);

        // Si composer audit retorna 0, no hay vulnerabilidades
        expect($result['advisories'] ?? [])->toBeEmpty(
            'Se encontraron vulnerabilidades en dependencias. Ejecuta: composer audit'
        );
    });
})->group('security', 'A06');
```

### Code Reviewer Checklist — A06

- [ ] `composer audit` pasa sin vulnerabilidades críticas/altas
- [ ] Las versiones en `composer.json` no están ancladas a versiones con CVEs conocidos
- [ ] PHP runtime está en una versión con soporte activo (≥8.2)
- [ ] Laravel está en una versión LTS o actual con soporte de seguridad
- [ ] Existe un job automatizado en CI que ejecuta `composer audit` periódicamente

---

## A07:2021 — Identification and Authentication Failures (Fallos de Autenticación)

**Descripción**: Autenticación débil, gestión insegura de sesiones o credenciales.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Autenticación robusta con Laravel Fortify/Sanctum

// Política de contraseñas robusta
// app/Providers/AppServiceProvider.php
use Illuminate\Validation\Rules\Password;

public function boot(): void
{
    Password::defaults(function () {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(); // Verifica contra HaveIBeenPwned API
    });
}

// Rotación de ID de sesión tras login (previene Session Fixation)
// Laravel Fortify lo hace automáticamente con session()->regenerate()

// Multi-Factor Authentication — Con Laravel Fortify
// config/fortify.php
// 'features' => [
//     Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
// ],

// Invalidar sesiones concurrentes (single session)
// app/Http/Controllers/AuthController.php
public function login(LoginRequest $request): JsonResponse
{
    if (!Auth::attempt($request->only('email', 'password'))) {
        throw ValidationException::withMessages([
            'email' => ['Las credenciales no coinciden.'],
        ]);
    }

    $request->session()->regenerate(); // ✅ Previene Session Fixation

    // Invalidar tokens Sanctum anteriores del mismo usuario
    $request->user()->tokens()->delete();
    $token = $request->user()->createToken('api-token')->plainTextToken;

    return response()->json(['token' => $token]);
}
```

### BDD Test

```php
describe('[A07] Authentication Failures', function () {
    it('GIVEN credenciales incorrectas WHEN intenta login THEN recibe 422 sin detalles de qué falló', function () {
        $response = postJson('/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        // No debe decir "el email no existe" o "la contraseña es incorrecta"
        $response->assertJsonValidationErrors(['email']);
    });

    it('GIVEN login exitoso WHEN se regenera sesión THEN el ID de sesión cambia', function () {
        $user       = User::factory()->create(['password' => Hash::make('ValidP@ss123')]);
        $sessionBefore = session()->getId();

        postJson('/auth/login', ['email' => $user->email, 'password' => 'ValidP@ss123']);

        expect(session()->getId())->not->toBe($sessionBefore);
    });

    it('GIVEN contraseña débil WHEN registra usuario THEN falla validación', function () {
        $response = postJson('/auth/register', [
            'email'    => 'new@example.com',
            'password' => '12345', // Débil
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
})->group('security', 'A07');
```

### Code Reviewer Checklist — A07

- [ ] Las contraseñas usan la regla `Password::defaults()` con requisitos fuertes
- [ ] El login regenera el ID de sesión tras autenticación exitosa
- [ ] Los mensajes de error de login no distinguen entre email/contraseña incorrectos
- [ ] Los tokens de API tienen un TTL definido y se invalidan en logout
- [ ] La recuperación de contraseña invalida el token tras su primer uso
- [ ] MFA está disponible o es obligatorio para roles privilegiados

---

## A08:2021 — Software and Data Integrity Failures (Fallos de Integridad)

**Descripción**: Deserialización insegura, actualizaciones sin verificación, pipelines CI/CD comprometidos.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Firmar y verificar datos sensibles

// Usar HMAC para verificar integridad de datos en URLs/tokens
use Illuminate\Support\Str;

// Generar URL firmada (Laravel built-in)
$signedUrl = URL::signedRoute('resource.download', ['id' => $resource->id]);

// Verificar URL firmada en el controlador
public function download(Request $request, Resource $resource): Response
{
    if (!$request->hasValidSignature()) {
        abort(403, 'URL inválida o expirada.');
    }
    return response()->download(storage_path("app/{$resource->path}"));
}

// Jobs en cola: validar el estado antes de ejecutar
final class ProcessPaymentJob implements ShouldQueue
{
    public function handle(): void
    {
        // ✅ Re-validar estado fresco de BD, no confiar en el estado del objeto serializado
        $payment = Payment::findOrFail($this->paymentId);

        if ($payment->status !== PaymentStatus::Pending) {
            return; // Ya fue procesado, evitar doble procesamiento
        }

        // ... procesar
    }
}

// ❌ NUNCA usar unserialize() con datos de usuario
// $data = unserialize($_COOKIE['user_data']); // PELIGROSO: Object Injection

// ✅ Usar JSON en su lugar
$data = json_decode(base64_decode($_COOKIE['user_data']), true);
// O mejor aún, firmar el JWT/cookie con Crypt::encrypt()
```

### BDD Test

```php
describe('[A08] Integrity Failures', function () {
    it('GIVEN URL de descarga WHEN la firma es inválida THEN retorna 403', function () {
        $resource = Resource::factory()->create();
        $tamperedUrl = "/resources/{$resource->id}/download?signature=fake-sig";

        get($tamperedUrl)->assertForbidden();
    });

    it('GIVEN payload de job WHEN el recurso fue eliminado THEN el job termina gracefully', function () {
        $paymentId = 999999; // ID inexistente

        expect(fn () => (new ProcessPaymentJob($paymentId))->handle())
            ->toThrow(ModelNotFoundException::class);
    });
})->group('security', 'A08');
```

### Code Reviewer Checklist — A08

- [ ] No se usa `unserialize()` con datos de fuentes externas (cookies, API, usuarios)
- [ ] Las URLs de recursos privados usan `URL::signedRoute()` de Laravel
- [ ] Los jobs de cola validan el estado actual desde BD antes de ejecutar
- [ ] Los webhooks externos verifican la firma HMAC del proveedor (Stripe, GitHub, etc.)
- [ ] Las dependencias en `composer.lock` se verifican con hash en CI/CD
- [ ] No hay `eval()` con contenido dinámico externo

---

## A09:2021 — Security Logging and Monitoring Failures (Fallos en Logging)

**Descripción**: Ausencia de logs de eventos de seguridad o monitoreo de incidentes.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Logging estructurado de eventos de seguridad

use Illuminate\Support\Facades\Log;

// Eventos a registrar obligatoriamente:
final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            // ✅ Log de intento fallido de autenticación
            Log::warning('Authentication failure', [
                'email'      => $request->email,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp'  => now()->toIso8601String(),
            ]);

            throw ValidationException::withMessages(['email' => ['Credenciales inválidas.']]);
        }

        // ✅ Log de login exitoso
        Log::info('User authenticated', [
            'user_id'    => Auth::id(),
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp'  => now()->toIso8601String(),
        ]);

        return response()->json(['token' => $this->generateToken()]);
    }
}

// ✅ Evento de acceso denegado
final class SecurityAuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->status() === 403) {
            Log::warning('Access denied', [
                'user_id' => Auth::id(),
                'path'    => $request->path(),
                'method'  => $request->method(),
                'ip'      => $request->ip(),
            ]);
        }

        return $response;
    }
}

// config/logging.php — Canal de seguridad dedicado
// 'channels' => [
//     'security' => [
//         'driver' => 'daily',
//         'path'   => storage_path('logs/security.log'),
//         'level'  => 'warning',
//         'days'   => 90,  // Retención de 90 días
//     ],
// ]

// ❌ NUNCA loguear:
// Log::info('User login', ['password' => $password]);  // Contraseñas en logs
// Log::info('Card payment', ['card_number' => $card]); // PAN/CVV en logs
```

### BDD Test

```php
describe('[A09] Security Logging', function () {
    it('GIVEN login fallido WHEN ocurre THEN se registra en el log con IP', function () {
        Log::spy();

        postJson('/auth/login', ['email' => 'fake@test.com', 'password' => 'wrong']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Authentication failure', Mockery::subset(['ip' => '127.0.0.1']));
    });

    it('GIVEN login exitoso WHEN ocurre THEN se registra con user_id', function () {
        $user = User::factory()->create(['password' => Hash::make('ValidP@ss123!')]);
        Log::spy();

        postJson('/auth/login', ['email' => $user->email, 'password' => 'ValidP@ss123!']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('User authenticated', Mockery::subset(['user_id' => $user->id]));
    });
})->group('security', 'A09');
```

### Code Reviewer Checklist — A09

- [ ] Los intentos de login fallidos se loguean con IP, user agent y timestamp
- [ ] Los accesos denegados (403) se loguean con el contexto del usuario
- [ ] Los logs de seguridad NO contienen contraseñas, tokens ni datos de tarjetas
- [ ] Existe un canal de log dedicado para eventos de seguridad con retención ≥90 días
- [ ] Las acciones administrativas críticas (cambios de roles, eliminación masiva) se auditan
- [ ] Los logs son inmutables (write-only) y están fuera del directorio web público

---

## A10:2021 — Server-Side Request Forgery (SSRF)

**Descripción**: La aplicación obtiene recursos de URLs controladas por el atacante.

### Mitigación en Laravel

```php
<?php
// ✅ CORRECTO: Validar y restringir URLs antes de hacer peticiones HTTP

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\In;

// Validación estricta de URLs externas
final class WebhookService
{
    private const ALLOWED_SCHEMES = ['https'];
    private const BLOCKED_HOSTS   = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
    private const BLOCKED_RANGES  = ['10.', '172.16.', '192.168.', '169.254.'];

    public function fetchWebhookData(string $url): array
    {
        $this->validateUrl($url);

        return Http::timeout(5)
            ->withoutRedirecting()     // ✅ No seguir redirects que podrían apuntar a internos
            ->get($url)
            ->throw()
            ->json();
    }

    private function validateUrl(string $url): void
    {
        $parsed = parse_url($url);

        // ✅ Solo HTTPS
        if (!in_array($parsed['scheme'] ?? '', self::ALLOWED_SCHEMES, true)) {
            throw new \InvalidArgumentException('Solo se permiten URLs HTTPS.');
        }

        $host = $parsed['host'] ?? '';

        // ✅ Bloquear localhost y loopback
        foreach (self::BLOCKED_HOSTS as $blocked) {
            if ($host === $blocked) {
                throw new \InvalidArgumentException("Host bloqueado: {$host}");
            }
        }

        // ✅ Bloquear rangos de IP privadas (SSRF hacia servicios internos)
        foreach (self::BLOCKED_RANGES as $range) {
            if (str_starts_with($host, $range)) {
                throw new \InvalidArgumentException("Rango de IP privada bloqueado.");
            }
        }

        // ✅ Resolver DNS y verificar que la IP no es privada
        $resolvedIp = gethostbyname($host);
        foreach (self::BLOCKED_RANGES as $range) {
            if (str_starts_with($resolvedIp, $range)) {
                throw new \InvalidArgumentException("DNS resuelve a IP privada bloqueada.");
            }
        }
    }
}

// ✅ Para casos donde solo se necesita un subconjunto de dominios: allowlist
// Solo permitir URLs de dominios específicos conocidos
final class AvatarFetchService
{
    private const ALLOWED_DOMAINS = ['gravatar.com', 'avatars.githubusercontent.com'];

    public function fetchAvatar(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!in_array($host, self::ALLOWED_DOMAINS, true)) {
            throw new \InvalidArgumentException("Dominio no permitido: {$host}");
        }

        return Http::get($url)->body();
    }
}
```

### BDD Test

```php
describe('[A10] SSRF', function () {
    it('GIVEN URL interna WHEN se usa como webhook THEN es rechazada', function () {
        $internalUrls = [
            'http://localhost/admin',
            'http://127.0.0.1/etc/passwd',
            'http://192.168.1.1/router-admin',
            'http://169.254.169.254/latest/meta-data/', // AWS metadata
        ];

        foreach ($internalUrls as $url) {
            expect(fn () => app(WebhookService::class)->fetchWebhookData($url))
                ->toThrow(\InvalidArgumentException::class);
        }
    });

    it('GIVEN URL con scheme http WHEN se usa THEN es rechazada (solo HTTPS)', function () {
        expect(fn () => app(WebhookService::class)->fetchWebhookData('http://example.com'))
            ->toThrow(\InvalidArgumentException::class, 'Solo se permiten URLs HTTPS.');
    });

    it('GIVEN URL de dominio permitido WHEN se fetcha avatar THEN funciona correctamente', function () {
        Http::fake(['gravatar.com/*' => Http::response('image-data', 200)]);

        $avatar = app(AvatarFetchService::class)->fetchAvatar('https://gravatar.com/avatar/abc123');

        expect($avatar)->toBe('image-data');
    });
})->group('security', 'A10');
```

### Code Reviewer Checklist — A10

- [ ] Toda URL provista por el usuario pasa por validación de esquema (solo HTTPS)
- [ ] Se bloquean hosts internos: `localhost`, `127.0.0.1`, rangos RFC1918 (10.x, 192.168.x, 172.16.x)
- [ ] Las peticiones HTTP salientes tienen timeout corto y no siguen redirects automáticamente
- [ ] Se usa allowlist de dominios cuando el dominio destino es predecible
- [ ] Se verifica la resolución DNS de la URL para prevenir DNS rebinding
- [ ] Los servicios de metadata cloud (169.254.169.254, fd00:ec2::254) están bloqueados explícitamente

---

## RESUMEN: Checklist Maestro de Verificación OWASP

Ejecutar antes de cada merge a `main`/`master`:

```bash
#!/usr/bin/env bash
# scripts/security-check.sh

set -euo pipefail

echo "🔒 OWASP Security Check — $(date)"
echo "=================================="

echo ""
echo "📦 [A06] Verificando dependencias vulnerables..."
composer audit

echo ""
echo "🔍 [ALL] Análisis estático con PHPStan nivel 9..."
vendor/bin/phpstan analyse --level=9 --no-progress

echo ""
echo "🧪 [ALL] Ejecutando tests de seguridad..."
vendor/bin/pest --group=security --coverage --min=80

echo ""
echo "🔎 [A03] Buscando patrones inseguros en código fuente..."
INSECURE_PATTERNS=(
    "eval("
    "unserialize("
    "shell_exec("
    "\$_GET\["
    "\$_POST\["
    "md5("
    "sha1("
    "->toSql()"
)

FOUND=false
for pattern in "${INSECURE_PATTERNS[@]}"; do
    if grep -rn "$pattern" src/ 2>/dev/null; then
        echo "⚠️  Patrón inseguro encontrado: $pattern"
        FOUND=true
    fi
done

if [ "$FOUND" = false ]; then
    echo "✅ No se encontraron patrones inseguros"
fi

echo ""
echo "🌐 [A05] Verificando configuración de seguridad..."
php artisan config:show session | grep -E "secure|http_only|same_site"

echo ""
echo "✅ Security check completado."
```

### Tabla de Riesgos OWASP — Referencia Rápida

| ID | Riesgo | Mitigación Principal en Laravel |
|----|--------|---------------------------------|
| **A01** | Broken Access Control | Policies + Gates + `authorize()` |
| **A02** | Cryptographic Failures | `Hash::make()` + Cast `Encrypted` + HTTPS |
| **A03** | Injection | Eloquent ORM + FormRequest + `{{ }}` en Blade |
| **A04** | Insecure Design | Rate Limiting + Threat Modeling + Secure Defaults |
| **A05** | Security Misconfiguration | `APP_DEBUG=false` + Security Headers + HTTPS |
| **A06** | Vulnerable Components | `composer audit` + Versiones actualizadas |
| **A07** | Auth Failures | `Password::defaults()` + MFA + Session Regenerate |
| **A08** | Integrity Failures | `URL::signedRoute()` + No `unserialize()` + HMAC |
| **A09** | Logging Failures | Log estructurado de eventos de seguridad |
| **A10** | SSRF | Validación de URLs + Allowlist + Bloqueo IPs privadas |

---

## Configuración del Grupo de Tests de Seguridad

```php
// phpunit.xml — Agregar grupo de seguridad
// <group>security</group> se asigna en cada test con ->group('security', 'A0X')

// Ejecutar solo tests de seguridad:
// vendor/bin/pest --group=security

// Ejecutar tests de un riesgo específico:
// vendor/bin/pest --group=A01
// vendor/bin/pest --group=A03

// Ejecutar con cobertura mínima del 80% en el grupo de seguridad:
// vendor/bin/pest --group=security --coverage --min=80
```

---

## Recursos y Referencias

- 🔗 [OWASP Top Ten 2021](https://owasp.org/www-project-top-ten/)
- 🔗 [OWASP Laravel Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html)
- 🔗 [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- 🔗 [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- 🔗 [HaveIBeenPwned API](https://haveibeenpwned.com/API/v3) — Para `Password::uncompromised()`
