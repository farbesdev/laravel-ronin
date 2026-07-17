<?php

declare(strict_types=1);

namespace Laravel\Ronin\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ronin\Facades\Shinobi;
use Laravel\Ronin\Contracts\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Ronin\Exceptions\PermissionNotFoundException;

trait HasPermissions
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('shinobi.models.permission'))->withTimestamps();
    }

    /**
     * The mothergoose check. Runs through each scenario provided
     * by Shinobi - checking for special flags, role permissions, and
     * individual user permissions; in that order.
     * 
     * @param  Permission|String  $permission
     * @return boolean
     */
    public function hasPermissionTo($permission): bool
    {
        // Check role flags
        if (method_exists($this, 'hasPermissionRoleFlags') 
            && $this->hasPermissionRoleFlags() 
            && method_exists($this, 'hasPermissionThroughRoleFlag')
        ) {
            return $this->hasPermissionThroughRoleFlag();
        }

        if ((method_exists($this, 'hasPermissionFlags') and $this->hasPermissionFlags())) {
            return $this->hasPermissionThroughFlag();
        }
        
        // Fetch permission if we pass through a string
        if (is_string($permission)) {
            $permission = $this->getPermissionModel()->where('slug', $permission)->first();

            if (! $permission) {
                throw new PermissionNotFoundException;
            }
        }
        
        // Check role permissions
        if (method_exists($this, 'hasPermissionThroughRole') and $this->hasPermissionThroughRole($permission)) {
            return true;
        }
        
        // Check user permission
        if ($this->hasPermission($permission)) {
            return true;
        }

        return false;
    }
    
    /**
     * Give the specified permissions to the model.
     * 
     * @param  array  $permissions
     * @return self
     */
    public function givePermissionTo(...$permissions): self
    {        
        $permissions = Arr::flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        if (! $permissions) {
            return $this;
        }

        $this->permissions()->syncWithoutDetaching($permissions);

        return $this;
    }

    /**
     * Revoke the specified permissions from the model.
     * 
     * @param  array  $permissions
     * @return self
     */
    public function revokePermissionTo(...$permissions): self
    {
        $permissions = Arr::flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        $this->permissions()->detach($permissions);

        return $this;
    }

    /**
     * Sync the specified permissions against the model.
     * 
     * @param  array  $permissions
     * @return self
     */
    public function syncPermissions(...$permissions): self
    {
        $permissions = Arr::flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        $this->permissions()->sync($permissions);

        return $this;
    }

    /**
     * Get the specified permissions.
     * 
     * @param  array<int, mixed>  $collection
     * @return array<int, int>
     */
    protected function getPermissions(array $collection): array
    {
        return array_map(function($permission) {
            $model = $this->getPermissionModel();

            if ($permission instanceof Model) {
                return (int) $permission->getKey();
            }

            // @phpstan-ignore-next-line
            $permission = $model->where('slug', $permission)->first();

            return $permission->id;
        }, $collection);
    }

    /**
     * Checks if the user has the given permission assigned.
     * 
     * @param  \Laravel\Ronin\Models\Permission  $permission
     * @return boolean
     */
    protected function hasPermission($permission): bool
    {
        $model = $this->getPermissionModel();

        if ($permission instanceof Permission) {
            $permission = $permission->slug;
        }

        return (bool) $this->permissions->where('slug', $permission)->count();
    }

    /**
     * Get the model instance responsible for permissions.
     * 
     * @return mixed
     */
    protected function getPermissionModel(): mixed
    {
        $permissionModel = app()->make(config('shinobi.models.permission'));

        if (! config('shinobi.cache.enabled')) {
            return $permissionModel;
        }

        $cacheStore = cache()->store();

        if (method_exists($cacheStore, 'tags')) {
            return $cacheStore->tags(config('shinobi.cache.tag'))->remember(
                'permissions',
                config('shinobi.cache.length'),
                function () use ($permissionModel) {
                    return $permissionModel->get();
                }
            );
        }

        return $cacheStore->remember(
            'permissions',
            config('shinobi.cache.length'),
            function () use ($permissionModel) {
                return $permissionModel->get();
            }
        );
    }
}
