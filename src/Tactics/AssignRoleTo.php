<?php

declare(strict_types=1);

namespace Laravel\Ronin\Tactics;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class AssignRoleTo
{
    /** @var array<int, mixed> */
    protected array $roles;

    /**
     * Create a new AssignRoleTo instance.
     *
     * @param  mixed  ...$roles
     */
    public function __construct(mixed ...$roles)
    {
        $this->roles = Arr::flatten($roles);
    }

    /**
     * Assign the roles to the given user.
     *
     * @param  Model  $user
     * @return void
     */
    public function to(Model $user): void
    {
        $user->assignRoles($this->roles); // @phpstan-ignore method.notFound
    }
}