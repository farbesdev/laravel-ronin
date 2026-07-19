<?php

declare(strict_types=1);

namespace Ronin\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Dispatched when a user is denied access due to insufficient roles or permissions.
 * 
 * Consumers can listen to this event to implement:
 * - Security logging (A09)
 * - Intrusion detection alerts
 * - Audit trails
 *
 * @see \Ronin\Middleware\UserHasRole
 * @see \Ronin\Middleware\UserHasAllRoles
 * @see \Ronin\Middleware\UserHasAnyRole
 */
final class AccessDenied
{
    public function __construct(
        /** The authenticated user who was denied access (null = unauthenticated guest). */
        public readonly Authenticatable|null $user,

        /** The role(s) or permission(s) that were required but not present. */
        public readonly string $requiredAccess,

        /** The HTTP method of the denied request (GET, POST, etc.). */
        public readonly string $method,

        /** The URI that was denied access to. */
        public readonly string $uri,

        /** The IP address of the requester (null in CLI/test environments). */
        public readonly string|null $ip,
    ) {}
}
