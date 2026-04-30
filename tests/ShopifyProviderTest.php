<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\ShopifyProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShopifyProviderTest extends TestCase
{
    #[Test]
    public function it_verifies_a_valid_signature(): void
    {
        $secret = 'shopify-webhook-secret';
        $payload = json_encode(['id' => 123, 'email' => 'test@example.com']);
        $signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('header')->with('X-Shopify-Hmac-Sha256')->andReturns($signature);
        $request->allows('header')->with('X-Shopify-Topic')->andReturns('orders/created');
        $request->allows('input')->with('id')->andReturns(123);
        $request->allows('all')->andReturns(['id' => 123]);

        $provider = new ShopifyProvider($secret);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_an_invalid_signature(): void
    {
        $this->expectException(HttpException::class);

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns(json_encode(['id' => 123]));
        $request->allows('header')->with('X-Shopify-Hmac-Sha256')->andReturns('invalidsignature');

        $provider = new ShopifyProvider('shopify-webhook-secret');
        $provider->receive($request);
    }

    #[Test]
    public function it_converts_topic_slashes_to_underscores(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-Shopify-Topic')->andReturns('orders/created');

        $provider = new ShopifyProvider('secret');

        $this->assertEquals('orders_created', $provider->getEvent($request));
    }

    #[Test]
    public function it_returns_empty_string_when_no_topic(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('X-Shopify-Topic')->andReturns(null);

        $provider = new ShopifyProvider('secret');

        $this->assertEquals('', $provider->getEvent($request));
    }
}
