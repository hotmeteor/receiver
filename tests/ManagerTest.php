<?php

namespace Receiver\Tests;

use PHPUnit\Framework\Attributes\Test;
use Receiver\Contracts\Factory;
use Receiver\Providers\GithubProvider;
use Receiver\Providers\PostmarkProvider;

class ManagerTest extends TestCase
{
    #[Test]
    public function it_can_instantiate_the_github_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $provider = $factory->driver('github');

        $this->assertInstanceOf(GithubProvider::class, $provider);
    }

    #[Test]
    public function it_can_instantiate_the_postmark_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $provider = $factory->driver('postmark');

        $this->assertInstanceOf(PostmarkProvider::class, $provider);
    }
}
