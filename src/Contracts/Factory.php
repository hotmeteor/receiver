<?php

namespace Receiver\Contracts;

interface Factory
{
    /**
     * Get an webhook provider implementation.
     *
     * @param  string  $driver
     * @return \Receiver\Contracts\Provider
     */
    public function driver($driver = null);
}
