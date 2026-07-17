<?php

declare(strict_types=1);

namespace Laravel\Ronin\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Role
{
    /**
     * Roles can belong to many users.
     */
    public function users(): BelongsToMany;

    public function hasPermissionFlags(): bool;

    public function hasPermissionThroughFlag(): bool;
}