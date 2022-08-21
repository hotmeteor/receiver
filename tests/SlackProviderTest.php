<?php

namespace Receiver\Tests;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Receiver\Providers\SlackProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SlackProviderTest extends TestCase
{
    /**
     * https://api.slack.com/apis/connections/events-api#the-events-api__subscribing-to-event-types__events-api-request-urls__request-url-configuration--verification.
     *
     * @return void
     */
    public function test_it_can_receive_slack_handshake()
    {
        $request = Mockery::mock(Request::class);
        $request->allows('has')->with('challenge')->andReturns(true);
        $request->allows('only')->with('challenge')->andReturns(['challenge' => 'slack-challenge-token']);

        $provider = new SlackProvider($this->app['config']->get('services.slack.webhook_secret'));
        $provider->receive($request);

        $webhook = $provider->webhook();
        $response = $provider->toResponse($request);

        $this->assertNull($webhook);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson(json_encode(['challenge' => 'slack-challenge-token']), $response->content());
    }

    public function test_it_can_sign_and_verify_slack_webhook()
    {
        $valid_signature = 'v0=9cd89ead8bc70cf63775d36d04c592a4833c253d9f0f0c0b21762f0f6e9ae429';
        $time = Carbon::parse('2022-08-01 12:00:00', 'America/Chicago');

        Carbon::setTestNow($time);

        $request = Mockery::mock(Request::class);
        $request->allows('has')->with('challenge')->andReturnNull();

        $this->signUsing($request, $time->unix(), $valid_signature);

        $request->allows('input')->with('event.type')->andReturns($this->mockPayload('event.type'));
        $request->allows('all')->andReturns($this->mockPayload());

        $provider = new SlackProvider($this->app['config']->get('services.slack.webhook_secret'));
        $provider->receive($request);

        $webhook = $provider->webhook();

        $this->assertInstanceOf(Webhook::class, $webhook);
    }

    public function test_it_can_sign_and_deny_slack_webhook()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $invalid_signature = 'v0=invalid';
        $time = Carbon::parse('2022-08-01 12:00:00', 'America/Chicago');

        Carbon::setTestNow($time);

        $request = Mockery::mock(Request::class);
        $request->allows('has')->with('challenge')->andReturnNull();

        $this->signUsing($request, $time->unix(), $invalid_signature);

        $provider = new SlackProvider($this->app['config']->get('services.slack.webhook_secret'));
        $provider->receive($request);
    }

    /**
     * @param Request $request
     * @param int $timestamp
     * @param string $signature
     * @return Request
     */
    protected function signUsing(Request $request, int $timestamp, string $signature): Request
    {
        $request->allows('header')->with('X-Slack-Request-Timestamp')->andReturns($timestamp);
        $request->allows('getContent')->andReturns(json_encode($this->mockPayload()));
        $request->allows('header')->with('X-Slack-Signature')->andReturns($signature);

        return $request;
    }

    /**
     * https://api.slack.com/apis/connections/events-api#the-events-api__receiving-events__event-type-structure.
     *
     * @param string|null $key
     * @return mixed
     */
    protected function mockPayload(string $key = null): mixed
    {
        $data = [
            'token' => 'z26uFbvR1xHJEdHE1OQiO6t8',
            'team_id' => 'T061EG9RZ',
            'api_app_id' => 'A0FFV41KK',
            'event' => [
                'type' => 'reaction_added',
                'user' => 'U061F1EUR',
                'item' => [
                    'type' => 'message',
                    'channel' => 'C061EG9SL',
                    'ts' => '1464196127.000002',
                ],
                'reaction' => 'slightly_smiling_face',
                'item_user' => 'U0M4RL1NY',
                'event_ts' => '1465244570.336841',
            ],
            'type' => 'event_callback',
            'authed_users' => [
                'U061F7AUR',
            ],
            'authorizations' => [
                [
                    'enterprise_id' => 'E12345',
                    'team_id' => 'T12345',
                    'user_id' => 'U12345',
                    'is_bot' => false,
                ],
            ],
            'event_id' => 'Ev9UQ52YNA',
            'event_context' => 'EC12345',
            'event_time' => 1234567890,
        ];

        return $key ? data_get($data, $key) : $data;
    }
}
