<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mockery;
use Receiver\Providers\PostmarkProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostmarkProviderTest extends TestCase
{
    // -------------------------------------------------------------------------
    // verify() — auth mode
    // -------------------------------------------------------------------------

    public function test_it_can_verify_postmark_webhook_via_auth()
    {
        Config::set('services.postmark.webhook.verification_types', ['auth']);

        Auth::shouldReceive('onceBasic')->once()->andReturnNull();

        $request = $this->mockBaseRequest();

        $provider = new PostmarkProvider(null);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    public function test_it_denies_postmark_webhook_with_invalid_auth()
    {
        Config::set('services.postmark.webhook.verification_types', ['auth']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        Auth::shouldReceive('onceBasic')->once()->andReturn(response('Unauthorized', 401));

        $request = $this->mockBaseRequest();

        $provider = new PostmarkProvider(null);
        $provider->receive($request);
    }

    // -------------------------------------------------------------------------
    // verify() — headers mode
    // -------------------------------------------------------------------------

    public function test_it_can_verify_postmark_webhook_via_valid_headers()
    {
        Config::set('services.postmark.webhook.verification_types', ['headers']);

        $request = $this->mockBaseRequest();
        $request->allows('hasHeader')->with('X-Custom-Header')->andReturns(true);
        $request->allows('header')->with('X-Custom-Header')->andReturns('PostmarkExpected');

        $provider = new PostmarkProvider(null);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    public function test_it_denies_postmark_webhook_with_missing_header()
    {
        Config::set('services.postmark.webhook.verification_types', ['headers']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = $this->mockBaseRequest();
        $request->allows('hasHeader')->with('X-Custom-Header')->andReturns(false);

        $provider = new PostmarkProvider(null);
        $provider->receive($request);
    }

    public function test_it_denies_postmark_webhook_with_wrong_header_value()
    {
        Config::set('services.postmark.webhook.verification_types', ['headers']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = $this->mockBaseRequest();
        $request->allows('hasHeader')->with('X-Custom-Header')->andReturns(true);
        $request->allows('header')->with('X-Custom-Header')->andReturns('WrongValue');

        $provider = new PostmarkProvider(null);
        $provider->receive($request);
    }

    // -------------------------------------------------------------------------
    // verify() — IPs mode
    // -------------------------------------------------------------------------

    public function test_it_can_verify_postmark_webhook_via_allowed_ip()
    {
        Config::set('services.postmark.webhook.verification_types', ['ips']);

        $request = $this->mockBaseRequest();
        $request->allows('getClientIp')->andReturns('3.134.147.250');

        $provider = new PostmarkProvider(null);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    public function test_it_denies_postmark_webhook_from_disallowed_ip()
    {
        Config::set('services.postmark.webhook.verification_types', ['ips']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = $this->mockBaseRequest();
        $request->allows('getClientIp')->andReturns('1.2.3.4');

        $provider = new PostmarkProvider(null);
        $provider->receive($request);
    }

    // -------------------------------------------------------------------------
    // verify() — no verification_types configured
    // -------------------------------------------------------------------------

    public function test_it_passes_when_no_verification_types_configured()
    {
        Config::set('services.postmark.webhook.verification_types', []);

        $request = $this->mockBaseRequest();

        $provider = new PostmarkProvider(null);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    // -------------------------------------------------------------------------
    // getEvent()
    // -------------------------------------------------------------------------

    public function test_it_gets_record_type_event()
    {
        $request = Mockery::mock(Request::class);
        $request->allows('filled')->with('RecordType')->andReturns(true);
        $request->allows('input')->with('RecordType')->andReturns('Bounce');

        $provider = new PostmarkProvider(null);

        $this->assertEquals('Bounce', $provider->getEvent($request));
    }

    public function test_it_defaults_to_inbound_event()
    {
        $request = Mockery::mock(Request::class);
        $request->allows('filled')->with('RecordType')->andReturns(false);

        $provider = new PostmarkProvider(null);

        $this->assertEquals('Inbound', $provider->getEvent($request));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function mockBaseRequest(): Request
    {
        $payload = [
            'RecordType' => 'Delivery',
            'MessageID' => '883953f4-6105-42a2-a16a-77a8eac79483',
        ];

        $request = Mockery::mock(Request::class);
        $request->allows('filled')->with('RecordType')->andReturns(true);
        $request->allows('input')->with('RecordType')->andReturns('Delivery');
        $request->allows('all')->andReturns($payload);

        return $request;
    }
}
