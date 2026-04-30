<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\TwilioProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TwilioProviderTest extends TestCase
{
    #[Test]
    public function it_verifies_a_valid_signature(): void
    {
        $secret = 'twilio-webhook-secret';
        $url = 'https://example.com/webhook';
        $params = ['MessageSid' => 'SM123', 'MessageStatus' => 'delivered'];

        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key.$value;
        }
        $signature = base64_encode(hash_hmac('sha1', $data, $secret, true));

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-Twilio-Signature')->andReturns($signature);
        $request->allows('fullUrl')->andReturns($url);
        $request->allows('post')->andReturns($params);
        $request->allows('input')->with('EventType')->andReturns(null);
        $request->allows('input')->with('MessageStatus')->andReturns('delivered');
        $request->allows('all')->andReturns($params);

        $provider = new TwilioProvider($secret);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_an_invalid_signature(): void
    {
        $this->expectException(HttpException::class);

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-Twilio-Signature')->andReturns('invalidsig');
        $request->allows('fullUrl')->andReturns('https://example.com/webhook');
        $request->allows('post')->andReturns([]);

        $provider = new TwilioProvider('twilio-webhook-secret');
        $provider->receive($request);
    }

    #[Test]
    public function it_returns_false_when_signature_header_missing(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-Twilio-Signature')->andReturns(null);
        $request->allows('fullUrl')->andReturns('https://example.com/webhook');
        $request->allows('post')->andReturns([]);

        $provider = new TwilioProvider('secret');

        $this->assertFalse($provider->verify($request));
    }

    #[Test]
    public function it_returns_message_status_as_event(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('EventType')->andReturns(null);
        $request->allows('input')->with('MessageStatus')->andReturns('delivered');

        $provider = new TwilioProvider('secret');

        $this->assertEquals('delivered', $provider->getEvent($request));
    }

    #[Test]
    public function it_returns_call_status_as_event(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('EventType')->andReturns(null);
        $request->allows('input')->with('MessageStatus')->andReturns(null);
        $request->allows('input')->with('CallStatus')->andReturns('completed');

        $provider = new TwilioProvider('secret');

        $this->assertEquals('completed', $provider->getEvent($request));
    }

    #[Test]
    public function it_prefers_event_type_over_status_fields(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('EventType')->andReturns('com.twilio.messaging.message.delivered');

        $provider = new TwilioProvider('secret');

        $this->assertEquals('com.twilio.messaging.message.delivered', $provider->getEvent($request));
    }
}
