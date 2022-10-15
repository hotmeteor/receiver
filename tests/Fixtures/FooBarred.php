<?php

namespace Receiver\Tests\Fixtures;

class FooBarred
{
    public function __construct(public string $event, public array $data)
    {
    }

    public function handle()
    {
        //
    }
}
