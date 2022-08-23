<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Receiver\Providers\GithubProvider;
use Receiver\Providers\Webhook;

class GithubProviderTest extends TestCase
{
    public function test_it_can_receive_github_webhook()
    {
        Log::partialMock()->shouldReceive('info')->never();

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-GitHub-Event')->andReturns('issues');
        $request->allows('input')->with('action')->andReturns('opened');
        $request->allows('input')->with('issue')->andReturns($this->mockPayload('issue'));
        $request->allows('all')->andReturns($this->mockPayload());

        $provider = new GithubProvider($this->app['config']->get('services.github.webhook_secret'));
        $provider->receive($request);

        $webhook = $provider->webhook();

        $this->assertInstanceOf(Webhook::class, $webhook);
    }

    public function test_it_dispatches_matching_handler()
    {
        Log::partialMock()->shouldReceive('info')->withArgs(['Webhook handled.'])->andReturnNull();

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-GitHub-Event')->andReturns('issues');
        $request->allows('input')->with('action')->andReturns('closed');
        $request->allows('input')->with('issue')->andReturns($this->mockPayload('issue'));
        $request->allows('all')->andReturns($this->mockPayload());

        $config = $this->app['config']->get('services.github');

        $provider = new GithubProvider($config['webhook_secret']);
        $provider->setHandlerNamespace('Receiver\\Tests\\Fixtures');
        $provider->receive($request);

        $webhook = $provider->webhook();

        $this->assertInstanceOf(Webhook::class, $webhook);
    }

    /**
     * https://docs.github.com/en/developers/webhooks-and-events/webhooks/webhook-events-and-payloads#example-delivery.
     *
     * @param string|null $key
     * @return mixed
     */
    protected function mockPayload(string $key = null): mixed
    {
        $data = [
            [
                'issue' => [
                    [
                        'url' => 'https://api.github.com/repos/octocat/Hello-World/issues/1347',
                        'number' => 1347,
                    ],
                ],
                'repository' => [
                    'id' => 1296269,
                    'full_name' => 'octocat/Hello-World',
                    'owner' => [
                        'login' => 'octocat',
                        'id' => 1,
                    ],
                ],
            ],
        ];

        return $key ? data_get($data, $key) : $data;
    }
}
