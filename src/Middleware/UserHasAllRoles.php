<?php

declare(strict_types=1);

namespace Ronin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Auth\Guard;
use Ronin\Events\AccessDenied;
use Illuminate\Support\Facades\Event;

class UserHasAllRoles
{
    protected Guard $auth;

    /**
     * Create a new UserHasPermission instance.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Run the request filter.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param mixed                    ...$roles
     *
     * @return mixed
     */
    public function handle($request, Closure $next, mixed ...$roles): mixed
    {
        $user = $this->auth->user();
        $roles = $this->normalizeRoles($roles);
        $authorized = false;

        if ($user && method_exists($user, 'hasAllRoles')) {
            $authorized = $user->hasAllRoles(...$roles);
        }

        if (! $authorized) {
            // [A09] Dispatch security event for audit logging
            Event::dispatch(new AccessDenied(
                user: $user,
                requiredAccess: implode('&', $roles),
                method: $request->method(),
                uri: $request->path(),
                ip: $request->ip(),
            ));

            // [A01] 401 = unauthenticated guest | 403 = authenticated but forbidden
            $statusCode = $user ? 403 : 401;

            if ($request->ajax()) {
                return response('Unauthorized.', $statusCode);
            }

            return abort($statusCode);
        }

        return $next($request);
    }

    protected function normalizeRoles(array $roles): array
    {
        if (count($roles) === 1 && is_array($roles[0])) {
            return Arr::flatten($roles[0]);
        }

        return Arr::flatten($roles);
    }
}
