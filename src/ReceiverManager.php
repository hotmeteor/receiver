<?php

namespace Receiver;

use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Receiver\Contracts\Factory;
use Receiver\Providers\AbstractProvider;
use Receiver\Providers\FakeProvider;
use Receiver\Providers\GithubProvider;
use Receiver\Providers\HubspotProvider;
use Receiver\Providers\MailchimpProvider;
use Receiver\Providers\PaddleProvider;
use Receiver\Providers\PostmarkProvider;
use Receiver\Providers\SendGridProvider;
use Receiver\Providers\ShopifyProvider;
use Receiver\Providers\SlackProvider;
use Receiver\Providers\StripeProvider;
use Receiver\Providers\TwilioProvider;

class ReceiverManager extends Manager implements Factory
{
    /**
     * Get a driver instance.
     */
    public function with(string $driver): mixed
    {
        return $this->driver($driver);
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createGithubDriver(): GithubProvider
    {
        $config = $this->config->get('services.github');

        return $this->buildProvider(
            GithubProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createHubspotDriver(): HubspotProvider
    {
        $config = $this->config->get('services.hubspot');

        return $this->buildProvider(
            HubspotProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createPostmarkDriver(): PostmarkProvider
    {
        $config = $this->config->get('services.postmark');

        return $this->buildProvider(
            PostmarkProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createSlackDriver(): SlackProvider
    {
        $config = $this->config->get('services.slack');

        return $this->buildProvider(
            SlackProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createStripeDriver(): StripeProvider
    {
        $config = $this->config->get('services.stripe');

        return $this->buildProvider(
            StripeProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createFakeDriver(): FakeProvider
    {
        return $this->buildProvider(
            FakeProvider::class,
            []
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createShopifyDriver(): ShopifyProvider
    {
        return $this->buildProvider(
            ShopifyProvider::class,
            $this->config->get('services.shopify')
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createTwilioDriver(): TwilioProvider
    {
        return $this->buildProvider(
            TwilioProvider::class,
            $this->config->get('services.twilio')
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createMailchimpDriver(): MailchimpProvider
    {
        return $this->buildProvider(
            MailchimpProvider::class,
            $this->config->get('services.mailchimp')
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createSendgridDriver(): SendGridProvider
    {
        return $this->buildProvider(
            SendGridProvider::class,
            $this->config->get('services.sendgrid')
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createPaddleDriver(): PaddleProvider
    {
        return $this->buildProvider(
            PaddleProvider::class,
            $this->config->get('services.paddle')
        );
    }

    /**
     * Build a webhook provider instance.
     *
     * @template T of AbstractProvider
     *
     * @param  class-string<T>  $provider
     * @return T
     */
    public function buildProvider(string $provider, array $config): AbstractProvider
    {
        return new $provider(
            Arr::get($config, 'webhook_secret')
        );
    }

    /**
     * Forget all the resolved driver instances.
     *
     * @return $this
     */
    public function forgetDrivers(): static
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Get the default driver name.
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No Receiver driver was specified.');
    }
}
