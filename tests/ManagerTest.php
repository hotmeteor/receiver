<?php

namespace Receiver\Tests;

use PHPUnit\Framework\Attributes\Test;
use Receiver\Contracts\Factory;
use Receiver\Providers\GithubProvider;
use Receiver\Providers\MailchimpProvider;
use Receiver\Providers\PaddleProvider;
use Receiver\Providers\PostmarkProvider;
use Receiver\Providers\SendGridProvider;
use Receiver\Providers\ShopifyProvider;
use Receiver\Providers\TwilioProvider;

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

    #[Test]
    public function it_can_instantiate_the_shopify_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $this->assertInstanceOf(ShopifyProvider::class, $factory->driver('shopify'));
    }

    #[Test]
    public function it_can_instantiate_the_twilio_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $this->assertInstanceOf(TwilioProvider::class, $factory->driver('twilio'));
    }

    #[Test]
    public function it_can_instantiate_the_mailchimp_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $this->assertInstanceOf(MailchimpProvider::class, $factory->driver('mailchimp'));
    }

    #[Test]
    public function it_can_instantiate_the_sendgrid_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $this->assertInstanceOf(SendGridProvider::class, $factory->driver('sendgrid'));
    }

    #[Test]
    public function it_can_instantiate_the_paddle_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $this->assertInstanceOf(PaddleProvider::class, $factory->driver('paddle'));
    }
}
