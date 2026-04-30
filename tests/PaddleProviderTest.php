<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\PaddleProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaddleProviderTest extends TestCase
{
    #[Test]
    public function it_verifies_a_valid_signature(): void
    {
        $secret = 'paddle-webhook-secret';
        $payload = json_encode($this->mockPayload());
        $timestamp = (string) time();
        $computed = hash_hmac('sha256', "{$timestamp}:{$payload}", $secret);
        $header = "ts={$timestamp};h1={$computed}";

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('Paddle-Signature')->andReturns($header);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('input')->with('event_type', '')->andReturns('transaction.created');
        $request->allows('all')->andReturns($this->mockPayload());

        $provider = new PaddleProvider($secret);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_an_invalid_signature(): void
    {
        $this->expectException(HttpException::class);

        $payload = json_encode($this->mockPayload());
        $header = 'ts='.time().';h1=invalidsignature';

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('Paddle-Signature')->andReturns($header);
        $request->allows('getContent')->andReturns($payload);

        $provider = new PaddleProvider('paddle-webhook-secret');
        $provider->receive($request);
    }

    #[Test]
    public function it_returns_false_when_signature_header_missing(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('Paddle-Signature')->andReturns(null);

        $provider = new PaddleProvider('secret');

        $this->assertFalse($provider->verify($request));
    }

    #[Test]
    public function it_accepts_any_matching_h1_during_key_rotation(): void
    {
        $secret = 'paddle-webhook-secret';
        $payload = json_encode($this->mockPayload());
        $timestamp = (string) time();
        $good = hash_hmac('sha256', "{$timestamp}:{$payload}", $secret);
        $header = "ts={$timestamp};h1=oldinvalidsignature;h1={$good}";

        $request = Mockery::mock(Request::class);
        $request->allows('header')->with('Paddle-Signature')->andReturns($header);
        $request->allows('getContent')->andReturns($payload);

        $provider = new PaddleProvider($secret);

        $this->assertTrue($provider->verify($request));
    }

    #[Test]
    public function it_returns_the_event_type(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('input')->with('event_type', '')->andReturns('subscription.created');

        $provider = new PaddleProvider('secret');

        $this->assertEquals('subscription.created', $provider->getEvent($request));
    }

    protected function mockPayload(): array
    {
        return [
            'event_type' => 'transaction.created',
            'data' => [
                'id' => 'txn_123',
                'status' => 'completed',
            ],
        ];
    }
}
