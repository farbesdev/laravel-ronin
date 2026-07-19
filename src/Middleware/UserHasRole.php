<?php

declare(strict_types=1);

namespace Ronin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Ronin\Events\AccessDenied;
use Illuminate\Support\Facades\Event;

class UserHasRole
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
     * @param string                   $role
     *
     * @return mixed
     */
    public function handle($request, Closure $next, string $role): mixed
    {
        $user = $this->auth->user();

        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole($role)) {
            // [A09] Dispatch security event for audit logging
            Event::dispatch(new AccessDenied(
                user: $user,
                requiredAccess: $role,
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
}
