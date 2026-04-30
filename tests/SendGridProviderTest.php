<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\SendGridProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SendGridProviderTest extends TestCase
{
    #[Test]
    public function it_skips_verification_when_no_secret_configured(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns(json_encode($this->mockBatch()));
        $request->allows('input')->with('event')->andReturns(null);
        $request->allows('all')->andReturns($this->mockBatch());

        $provider = new SendGridProvider('');
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_when_signature_header_is_missing(): void
    {
        $this->expectException(HttpException::class);

        $keyPair = $this->generateEcKeyPair();

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns('{}');
        $request->allows('header')->with('X-Twilio-Email-Event-Webhook-Signature')->andReturns(null);
        $request->allows('header')->with('X-Twilio-Email-Event-Webhook-Timestamp')->andReturns('1234567890');

        $provider = new SendGridProvider($keyPair['public']);
        $provider->receive($request);
    }

    #[Test]
    public function it_verifies_a_valid_ecdsa_signature(): void
    {
        $keyPair = $this->generateEcKeyPair();
        $timestamp = (string) time();
        $payload = json_encode($this->mockBatch());
        $toSign = $timestamp.$payload;

        openssl_sign($toSign, $rawSignature, $keyPair['private'], OPENSSL_ALGO_SHA256);
        $signature = base64_encode($rawSignature);

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('header')->with('X-Twilio-Email-Event-Webhook-Signature')->andReturns($signature);
        $request->allows('header')->with('X-Twilio-Email-Event-Webhook-Timestamp')->andReturns($timestamp);
        $request->allows('all')->andReturns($this->mockBatch());

        $provider = new SendGridProvider($keyPair['public']);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_an_invalid_ecdsa_signature(): void
    {
        $this->expectException(HttpException::class);

        $keyPair = $this->generateEcKeyPair();

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns(json_encode($this->mockBatch()));
        $request->allows('header')->with('X-Twilio-Email-Event-Webhook-Signature')->andReturns(base64_encode('invalidsig'));
        $request->allows('header')->with('X-Twilio-Email-Event-Webhook-Timestamp')->andReturns('1234567890');

        $provider = new SendGridProvider($keyPair['public']);
        $provider->receive($request);
    }

    #[Test]
    public function it_returns_a_map_of_unique_event_types(): void
    {
        $batch = $this->mockBatch();

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns(json_encode($batch));

        $provider = new SendGridProvider('');

        $result = $provider->getEvent($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('click', $result);
        $this->assertArrayHasKey('open', $result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function it_returns_only_the_first_event_per_type(): void
    {
        $batch = [
            ['event' => 'click', 'url' => 'https://first.example.com'],
            ['event' => 'click', 'url' => 'https://second.example.com'],
        ];

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns(json_encode($batch));

        $provider = new SendGridProvider('');

        $result = $provider->getEvent($request);

        $this->assertEquals('https://first.example.com', $result['click']['url']);
    }

    #[Test]
    public function it_returns_the_full_batch_as_data(): void
    {
        $batch = $this->mockBatch();

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns(json_encode($batch));

        $provider = new SendGridProvider('');

        $this->assertEquals($batch, $provider->getData($request));
    }

    protected function mockBatch(): array
    {
        return [
            [
                'email' => 'example@test.com',
                'timestamp' => 1460565976,
                'event' => 'click',
                'url' => 'https://example.com',
            ],
            [
                'email' => 'example@test.com',
                'timestamp' => 1460565977,
                'event' => 'open',
            ],
        ];
    }

    protected function generateEcKeyPair(): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($key, $privateKeyPem);
        $publicKeyPem = openssl_pkey_get_details($key)['key'];

        return ['private' => $privateKeyPem, 'public' => $publicKeyPem];
    }
}
