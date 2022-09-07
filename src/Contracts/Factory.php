<?php

namespace Receiver\Contracts;

interface Factory
{
    public function driver($driver = null);
}
