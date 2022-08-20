<?php

namespace Receiver\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Receiver\Contracts\Factory;

/**
 * @method static \Receiver\Contracts\Provider driver(string $driver = null)
 * @method static \Receiver\Contracts\Provider receive(Request $request)
 * @method static \Receiver\Contracts\Provider respond()
 */
class Receiver extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }
}
