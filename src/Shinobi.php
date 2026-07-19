<?php

declare(strict_types=1);

namespace Ronin;

use Ronin\Models\Role;
use Ronin\Models\Permission;
use Ronin\Tactics\AssignRoleTo;
use Ronin\Tactics\GivePermissionTo;
use Ronin\Tactics\RevokePermissionFrom;

class Shinobi
{
    /**
     * Fetch an instance of the Role model.
     */
    public function role(): Role
    {
        /** @var Role $model */
        $model = app()->make(config('ronin.models.role'));

        return $model;
    }

    /**
     * Fetch an instance of the Permission model.
     */
    public function permission(): Permission
    {
        /** @var Permission $model */
        $model = app()->make(config('ronin.models.permission'));

        return $model;
    }

    /**
     * Assign roles to a user.
     *
     * @param  mixed  ...$roles
     */
    public function assign(mixed ...$roles): AssignRoleTo
    {
        return new AssignRoleTo(...$roles);
    }

    /**
     * Give permissions to a user or role.
     *
     * @param  mixed  ...$permissions
     */
    public function give(mixed ...$permissions): GivePermissionTo
    {
        return new GivePermissionTo(...$permissions);
    }

    /**
     * Revoke permissions from a user or role.
     *
     * @param  mixed  ...$permissions
     */
    public function revoke(mixed ...$permissions): RevokePermissionFrom
    {
        return new RevokePermissionFrom(...$permissions);
    }
}
