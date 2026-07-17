<?php

declare(strict_types=1);

namespace Laravel\Ronin\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Permission
{
    /**
     * Permissions can belong to many roles.
     */
    public function roles(): BelongsToMany;
}