<?php

namespace Receiver\Tests;

use Receiver\Contracts\Factory;
use Receiver\Providers\GithubProvider;

class ManagerTest extends TestCase
{
    public function test_it_can_instantiate_the_github_driver()
    {
        $factory = $this->app->make(Factory::class);

        $provider = $factory->driver('github');

        $this->assertInstanceOf(GithubProvider::class, $provider);
    }
}
