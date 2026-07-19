<?php

declare(strict_types=1);

namespace Ronin\Tactics;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Ronin\Models\Role;

class GivePermissionTo
{
    /** @var array<int, mixed> */
    protected array $permissions;

    /**
     * Create a new GivePermissionTo instance.
     *
     * @param  mixed  ...$permissions
     */
    public function __construct(mixed ...$permissions)
    {
        $this->permissions = Arr::flatten($permissions);
    }

    /**
     * Give the permissions to the given user or role.
     *
     * @param  Model|string  $roleOrUser
     * @return void
     */
    public function to(Model|string $roleOrUser): void
    {
        if ($roleOrUser instanceof Model) {
            $instance = $roleOrUser;
        } else {
            /** @var Model $instance */
            $instance = Role::where('slug', $roleOrUser)->firstOrFail();
        }

        $instance->givePermissionTo($this->permissions); // @phpstan-ignore method.notFound
    }
}