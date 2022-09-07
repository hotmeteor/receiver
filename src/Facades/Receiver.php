<?php

namespace Receiver\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Receiver\Contracts\Factory;

/**
 * @method static \Receiver\Providers\AbstractProvider driver(string $driver = null)
 * @method static \Receiver\Providers\AbstractProvider receive(Request $request)
 * @method static \Symfony\Component\HttpFoundation\Response ok()
 * @see \Receiver\Contracts\Factory
 * @see \Receiver\Contracts\Provider
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
