<?php

namespace Receiver\Tests;

use Illuminate\Foundation\Application;
use Receiver\ReceiverServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @param $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.github', [
            'client_id' => 'github-client-id',
            'client_secret' => 'github-client-secret',
            'redirect' => 'http://your-callback-url',
            'webhook_secret' => 'github-webhook-secret',
        ]);

        $app['config']->set('services.slack', [
            'client_id' => 'slack-client-id',
            'client_secret' => 'slack-client-secret',
            'redirect' => 'http://your-callback-url',
            'webhook_secret' => 'slack-webhook-secret',
        ]);
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [ReceiverServiceProvider::class];
    }

    /**
     * Override application aliases.
     *
     * @param  Application  $app
     */
    protected function getPackageAliases($app): array
    {
        return ['Receiver' => 'Receiver\Receiver'];
    }
}
