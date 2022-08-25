<?php

namespace Receiver\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Receiver\Contracts\Factory;

/**
 * @method static \Receiver\Contracts\Factory driver(string $driver = null)
 * @method static \Receiver\Contracts\Factory receive(Request $request)
 * @method static \Receiver\Contracts\Factory respond()
 * @mixin \Receiver\Contracts\Factory
 * @mixin \Receiver\Contracts\Provider
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
