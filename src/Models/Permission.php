<?php

declare(strict_types=1);

namespace Laravel\Ronin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Ronin\Concerns\RefreshesPermissionCache;
use Laravel\Ronin\Contracts\Permission as PermissionContract;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Ronin\Models\Role> $roles
 */
class Permission extends Model implements PermissionContract
{
    use RefreshesPermissionCache;

    /**
     * The attributes that are fillable via mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'slug', 'description'];

    /**
     * Create a new Permission instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('shinobi.tables.permissions'));
    }

    /**
     * Permissions can belong to many roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(config('shinobi.models.role'))->withTimestamps();
    }
}
