<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\MailchimpProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MailchimpProviderTest extends TestCase
{
    #[Test]
    public function it_verifies_a_matching_secret(): void
    {
        $secret = 'mailchimp-webhook-secret';

        $request = Mockery::mock(Request::class);
        $request->allows('query')->with('secret')->andReturns($secret);
        $request->allows('input')->with('type', '')->andReturns('subscribe');
        $request->allows('all')->andReturns(['type' => 'subscribe', 'data' => []]);

        $provider = new MailchimpProvider($secret);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_a_mismatched_secret(): void
    {
        $this->expectException(HttpException::class);

        $request = Mockery::mock(Request::class);
        $request->allows('query')->with('secret')->andReturns('wrong-secret');

        $provider = new MailchimpProvider('mailchimp-webhook-secret');
        $provider->receive($request);
    }

    #[Test]
    public function it_returns_the_type_as_event(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('type', '')->andReturns('unsubscribe');

        $provider = new MailchimpProvider('secret');

        $this->assertEquals('unsubscribe', $provider->getEvent($request));
    }

    #[Test]
    public function it_returns_empty_string_when_no_type(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('type', '')->andReturns('');

        $provider = new MailchimpProvider('secret');

        $this->assertEquals('', $provider->getEvent($request));
    }
}
