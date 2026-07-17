<?php

declare(strict_types=1);

namespace Laravel\Ronin\Facades;

use Illuminate\Support\Facades\Facade;

class Shinobi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'shinobi';
    }
}
