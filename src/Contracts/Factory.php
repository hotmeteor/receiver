<?php

namespace Receiver\Contracts;

interface Factory
{
    /**
     * @param  string|null  $driver
     * @return mixed
     */
    public function driver($driver = null);
}
