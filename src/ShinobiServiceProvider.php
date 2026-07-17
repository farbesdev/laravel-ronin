<?php

declare(strict_types=1);

namespace Laravel\Ronin;

use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Authorizable;

class ShinobiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
            $this->publishMigrations();
        }

        $this->loadMigrations();
        $this->registerGates();
        $this->registerBladeDirectives();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/shinobi.php', 'shinobi'
        );

        $this->app->singleton('shinobi', function () {
            return new \Laravel\Ronin\Shinobi();
        });
    }

    /**
     * Register the permission gates.
     * 
     * @return void
     */
    protected function registerGates(): void
    {
        Gate::before(function (Authorizable $user, string $permission): bool|null {
            if (! method_exists($user, 'hasPermissionTo')) {
                return null;
            }

            try {
                return (bool) $user->hasPermissionTo($permission);
            } catch (Exception) {
                return null;
            }
        });
    }

    /**
     * Register the blade directives.
     *
     * @return void
     */
    protected function registerBladeDirectives(): void
    {
        Blade::if('role', function (string $role): bool {
            $user = auth()->user();

            return $user instanceof Authorizable
                && method_exists($user, 'hasRole')
                && $user->hasRole($role);
        });

        Blade::if('anyrole', function (mixed ...$roles): bool {
            $user = auth()->user();

            return $user instanceof Authorizable
                && method_exists($user, 'hasAnyRole')
                && $user->hasAnyRole(...$roles);
        });

        Blade::if('allroles', function (mixed ...$roles): bool {
            $user = auth()->user();

            return $user instanceof Authorizable
                && method_exists($user, 'hasAllRoles')
                && $user->hasAllRoles(...$roles);
        });
    }

    /**
     * Publish the config file.
     * 
     * @return void
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/shinobi.php' => config_path('shinobi.php'),
        ], 'config');
    }

    /**
     * Publish the migration files.
     * 
     * @return void
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__.'/../migrations/' => database_path('migrations'),
        ], 'migrations');
    }

    /**
     * Load our migration files.
     * 
     * @return void
     */
    protected function loadMigrations(): void
    {
        if (config('shinobi.migrate', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../migrations');
        }
    }
}
