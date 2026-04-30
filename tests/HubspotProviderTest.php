<?php

namespace Receiver\Tests;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Receiver\Providers\HubspotProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HubspotProviderTest extends TestCase
{
    public function test_it_can_receive_hubspot_webhook()
    {
        $secret = 'hubspot-webhook-secret';
        $time = Carbon::parse('2022-08-01 12:00:00', 'America/Chicago');
        Carbon::setTestNow($time);

        $method = 'POST';
        $uri = 'https://example.com/webhooks/hubspot';
        $body = json_encode($this->mockPayload());

        $signature = base64_encode(hash_hmac('sha256', $method.$uri.$body, $secret));

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-HubSpot-Request-Timestamp')->andReturns($time->unix());
        $request->allows('header')->with('X-HubSpot-Signature-v3')->andReturns($signature);
        $request->allows('method')->andReturns($method);
        $request->allows('getUri')->andReturns($uri);
        $request->allows('getContent')->andReturns($body);
        $request->allows('input')->with('eventType')->andReturns($this->mockPayload('eventType'));
        $request->allows('all')->andReturns($this->mockPayload());

        $provider = new HubspotProvider($secret);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    public function test_it_denies_invalid_hubspot_webhook()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $time = Carbon::parse('2022-08-01 12:00:00', 'America/Chicago');
        Carbon::setTestNow($time);

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-HubSpot-Request-Timestamp')->andReturns($time->unix());
        $request->allows('header')->with('X-HubSpot-Signature-v3')->andReturns('invalid-signature');
        $request->allows('method')->andReturns('POST');
        $request->allows('getUri')->andReturns('https://example.com/webhooks/hubspot');
        $request->allows('getContent')->andReturns(json_encode($this->mockPayload()));

        $provider = new HubspotProvider('hubspot-webhook-secret');
        $provider->receive($request);
    }

    public function test_it_denies_expired_hubspot_webhook()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $staleTimestamp = now()->subMinutes(10)->unix();

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-HubSpot-Request-Timestamp')->andReturns($staleTimestamp);

        $provider = new HubspotProvider('hubspot-webhook-secret');
        $provider->receive($request);
    }

    public function test_it_gets_event_from_event_type()
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('eventType')->andReturns('contact.creation');

        $provider = new HubspotProvider('secret');

        $this->assertEquals('contact.creation', $provider->getEvent($request));
    }

    protected function mockPayload(string $key = null): mixed
    {
        $data = [
            'eventType' => 'contact.creation',
            'objectId' => 123,
        ];

        return $key ? data_get($data, $key) : $data;
    }
}
