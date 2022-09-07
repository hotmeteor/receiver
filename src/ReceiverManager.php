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
use Receiver\Providers\PostmarkProvider;
use Receiver\Providers\SlackProvider;
use Receiver\Providers\StripeProvider;

class ReceiverManager extends Manager implements Factory
{
    /**
     * Get a driver instance.
     *
     * @param string $driver
     * @return mixed
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
     * Build a webhook provider instance.
     *
     * @param string $provider
     * @param array $config
     * @return AbstractProvider
     */
    public function buildProvider(string $provider, array $config): Providers\AbstractProvider
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
     * @throws \InvalidArgumentException
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No Receiver driver was specified.');
    }
}
