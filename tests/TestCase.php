<?php

namespace Receiver\Tests;

use Illuminate\Foundation\Application;
use Receiver\ReceiverServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
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

        $app['config']->set('services.shopify', [
            'webhook_secret' => 'shopify-webhook-secret',
        ]);

        $app['config']->set('services.twilio', [
            'webhook_secret' => 'twilio-webhook-secret',
        ]);

        $app['config']->set('services.mailchimp', [
            'webhook_secret' => 'mailchimp-webhook-secret',
        ]);

        $app['config']->set('services.sendgrid', [
            'webhook_secret' => '',
        ]);

        $app['config']->set('services.paddle', [
            'webhook_secret' => 'paddle-webhook-secret',
        ]);

        $app['config']->set('services.postmark.webhook', [
            'headers' => [
                'X-Custom-Header' => 'PostmarkExpected',
            ],
            'ips' => [
                '3.134.147.250',
                '50.31.156.6',
                '50.31.156.77',
                '18.217.206.57',
            ],
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
