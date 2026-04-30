<?php

namespace Receiver\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\StripeProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StripeProviderTest extends TestCase
{
    #[Test]
    public function it_can_receive_stripe_webhook(): void
    {
        $secret = 'stripe-test-secret';
        $timestamp = time();
        $payload = json_encode($this->mockPayload());

        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        $header = "t={$timestamp},v1={$signature}";

        $request = Mockery::mock(Request::class);
        $request->allows('has')->with('challenge')->andReturns(false);
        $request->allows('only')->with('challenge')->andReturns([]);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('header')->with('STRIPE_SIGNATURE')->andReturns($header);
        $request->allows('input')->with('type')->andReturns($this->mockPayload('type'));
        $request->allows('input')->with('data')->andReturns($this->mockPayload('data'));
        $request->allows('all')->andReturns($this->mockPayload());

        $provider = new StripeProvider($secret);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_invalid_stripe_signature(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = Mockery::mock(Request::class);
        $request->allows('has')->with('challenge')->andReturns(false);
        $request->allows('only')->with('challenge')->andReturns([]);
        $request->allows('getContent')->andReturns(json_encode($this->mockPayload()));
        $request->allows('header')->with('STRIPE_SIGNATURE')->andReturns('t=1234,v1=invalidsig');

        $provider = new StripeProvider('stripe-test-secret');
        $provider->receive($request);
    }

    #[Test]
    public function it_handles_stripe_handshake(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('has')->with('challenge')->andReturns(true);
        $request->allows('only')->with('challenge')->andReturns(['challenge' => 'stripe-challenge-token']);

        $provider = new StripeProvider('stripe-test-secret');
        $provider->receive($request);

        $this->assertNull($provider->webhook());

        $response = $provider->toResponse($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[Test]
    public function it_gets_event_from_type(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('type')->andReturns('customer.created');

        $provider = new StripeProvider('secret');

        $this->assertEquals('customer.created', $provider->getEvent($request));
    }

    #[Test]
    public function it_gets_data_from_data_key(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('data')->andReturns(['object' => ['id' => 'cus_123']]);

        $provider = new StripeProvider('secret');

        $this->assertEquals(['object' => ['id' => 'cus_123']], $provider->getData($request));
    }

    protected function mockPayload(?string $key = null): mixed
    {
        $data = [
            'type' => 'customer.created',
            'data' => [
                'object' => [
                    'id' => 'cus_123',
                    'email' => 'test@example.com',
                ],
            ],
        ];

        return $key ? data_get($data, $key) : $data;
    }
}
