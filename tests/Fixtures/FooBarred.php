<?php

namespace Receiver\Tests\Fixtures;

use Illuminate\Foundation\Bus\Dispatchable;

class FooBarred
{
    use Dispatchable;

    public function __construct(public string $event, public array $data)
    {
    }

    public function handle()
    {
        //
    }
}
