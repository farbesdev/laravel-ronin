<?php

declare(strict_types=1);

namespace Laravel\Ronin\Concerns;

trait RefreshesPermissionCache
{
    public static function bootRefreshesPermissionCache(): void
    {
        static::saved(function() {
            cache()->tags(config('shinobi.cache.tag'))->flush();
        });

        static::deleted(function() {
            cache()->tags(config('shinobi.cache.tag'))->flush();
        });
    }
}