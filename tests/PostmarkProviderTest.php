<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Mockery;
use Receiver\Providers\PostmarkProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostmarkProviderTest extends TestCase
{
    public function test_it_will_not_verify_postmark_webhook_when_no_verification_types_is_set()
    {
        Config::set('services.postmark.webhook.verification_types', []);

        $request = Mockery::mock(Request::class);

        $this->setupBaseRequest($request);

        $provider = new PostmarkProvider;
        $provider->receive($request);

        $webhook = $provider->webhook();

        $this->assertInstanceOf(Webhook::class, $webhook);
    }

    public function test_it_will_verify_postmark_webhook_with_valid_headers()
    {
        Config::set('services.postmark.webhook.verification_types', ['headers']);

        $request = Mockery::mock(Request::class);

        $this->setupBaseRequest($request);

        $request->allows('hasHeader')->with('foo')->andReturns(true);
        $request->allows('header')->with('foo')->andReturns('bar');

        $provider = new PostmarkProvider;
        $provider->receive($request);

        $webhook = $provider->webhook();

        $this->assertInstanceOf(Webhook::class, $webhook);
    }

    public function test_it_will_deny_postmark_webhook_with_missing_headers()
    {
        Config::set('services.postmark.webhook.verification_types', ['headers']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = Mockery::mock(Request::class);

        $this->setupBaseRequest($request);

        $request->allows('hasHeader')->with('foo')->andReturns(false);

        $provider = new PostmarkProvider;
        $provider->receive($request);
    }

    public function test_it_will_deny_postmark_webhook_with_invalid_headers()
    {
        Config::set('services.postmark.webhook.verification_types', ['headers']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = Mockery::mock(Request::class);

        $this->setupBaseRequest($request);

        $request->allows('hasHeader')->with('foo')->andReturns(true);
        $request->allows('header')->with('foo')->andReturns('baz');

        $request->allows('hasHeader')->with('foo')->andReturns(false);

        $provider = new PostmarkProvider;
        $provider->receive($request);
    }

    public function test_it_will_verify_postmark_webhook_with_valid_ip()
    {
        Config::set('services.postmark.webhook.verification_types', ['ips']);

        $request = Mockery::mock(Request::class);

        $this->setupBaseRequest($request);

        $request->allows('getClientIp')->andReturns('123.123.123.123');

        $provider = new PostmarkProvider;
        $provider->receive($request);

        $webhook = $provider->webhook();

        $this->assertInstanceOf(Webhook::class, $webhook);
    }

    public function test_it_will_deny_postmark_webhook_with_invalid_ip()
    {
        Config::set('services.postmark.webhook.verification_types', ['ips']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = Mockery::mock(Request::class);

        $this->setupBaseRequest($request);

        $request->allows('getClientIp')->andReturns('111.111.111.111');

        $provider = new PostmarkProvider;
        $provider->receive($request);
    }

    protected function setupBaseRequest(Request $request): Request
    {
        $request->allows('filled')->with('RecordType')->andReturns($this->mockPayload('RecordType'));
        $request->allows('input')->with('RecordType')->andReturns($this->mockPayload('RecordType'));
        $request->allows('all')->andReturns($this->mockPayload());
        $request->allows('header')->with('User-Agent')->andReturns('Postmark');
        $request->allows('hasHeader')->with('User-Agent')->andReturns(true);
        $request->allows('getContent')->andReturns(json_encode($this->mockPayload()));

        return $request;
    }

    /**
     * https://postmarkapp.com/developer/webhooks/delivery-webhook#testing-with-curl
     *
     * @param string|null $key
     * @return mixed
     */
    protected function mockPayload(string $key = null): mixed
    {
        $data = [
            'MessageID'   => '883953f4-6105-42a2-a16a-77a8eac79483',
            'Recipient'   => 'john@example.com',
            'DeliveredAt' => '2014-08-01T13:28:10.2735393-04:00',
            'Details'     => 'Test delivery webhook details',
            'Tag'         => 'welcome-email',
            'ServerID'    => 23,
            'Metadata'    => [
                'a_key' => 'a_value',
                'b_key' => 'b_value'
            ],
            'RecordType'    => 'Delivery',
            'MessageStream' => 'outbound',
        ];

        return $key ? data_get($data, $key) : $data;
    }
}
