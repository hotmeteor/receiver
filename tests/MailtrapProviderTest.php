<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\MailtrapProvider;
use Receiver\Providers\Webhook;
use Receiver\Tests\Fixtures\Mailtrap\InboundMessageReceived;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MailtrapProviderTest extends TestCase
{
    #[Test]
    public function it_verifies_a_valid_signature(): void
    {
        $secret = 'mailtrap-webhook-secret';
        $payload = $this->mockPayload();

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('header')->with('Mailtrap-Signature')->andReturns(hash_hmac('sha256', $payload, $secret));
        $request->allows('all')->andReturns(json_decode($payload, true));

        $provider = new MailtrapProvider($secret);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    #[Test]
    public function it_denies_an_invalid_signature(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($this->mockPayload());
        $request->allows('header')->with('Mailtrap-Signature')->andReturns('invalidsignature');

        $provider = new MailtrapProvider('mailtrap-webhook-secret');
        $provider->receive($request);
    }

    #[Test]
    public function it_returns_false_when_signature_header_missing(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($this->mockPayload());
        $request->allows('header')->with('Mailtrap-Signature')->andReturns(null);

        $provider = new MailtrapProvider('mailtrap-webhook-secret');

        $this->assertFalse($provider->verify($request));
    }

    #[Test]
    public function it_returns_false_when_no_secret_configured(): void
    {
        $payload = $this->mockPayload();

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('header')->with('Mailtrap-Signature')->andReturns(hash_hmac('sha256', $payload, ''));

        $provider = new MailtrapProvider(null);

        $this->assertFalse($provider->verify($request));
    }

    #[Test]
    public function it_verifies_the_raw_body_rather_than_reencoded_json(): void
    {
        $secret = 'mailtrap-webhook-secret';
        $payload = $this->mockPayload();
        $reencoded = json_encode(json_decode($payload, true));

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('header')->with('Mailtrap-Signature')->andReturns(hash_hmac('sha256', $reencoded, $secret));

        $provider = new MailtrapProvider($secret);

        $this->assertNotSame($payload, $reencoded);
        $this->assertFalse($provider->verify($request));
    }

    #[Test]
    public function it_groups_batched_events_by_type(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($this->mockPayload());

        $provider = new MailtrapProvider('secret');

        $result = $provider->getEvent($request);

        $this->assertSame(['inbound.message_received', 'delivery'], array_keys($result));
        $this->assertCount(2, $result['inbound.message_received']);
        $this->assertSame('1878217152073113664', $result['inbound.message_received'][0]['message_id']);
        $this->assertSame('1878217152073113665', $result['inbound.message_received'][1]['message_id']);
        $this->assertCount(1, $result['delivery']);
    }

    #[Test]
    public function it_returns_empty_string_when_no_events(): void
    {
        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns('{"events":[]}');

        $provider = new MailtrapProvider('secret');

        $this->assertEquals('', $provider->getEvent($request));
    }

    #[Test]
    public function it_dispatches_matching_handler(): void
    {
        Log::partialMock()->shouldReceive('info')->once()->withArgs(['Webhook handled.'])->andReturnNull();

        $secret = 'mailtrap-webhook-secret';
        $payload = $this->mockPayload();

        $request = Mockery::mock(Request::class);
        $request->allows('getContent')->andReturns($payload);
        $request->allows('header')->with('Mailtrap-Signature')->andReturns(hash_hmac('sha256', $payload, $secret));
        $request->allows('all')->andReturns(json_decode($payload, true));

        $provider = new MailtrapProvider($secret);
        $provider->setHandlerNamespace('Receiver\\Tests\\Fixtures');
        $provider->receive($request);

        $this->assertTrue($provider->dispatched(InboundMessageReceived::class));
    }

    /**
     * Raw body as sent by Mailtrap, which escapes "<" and ">" as \u003c and \u003e.
     *
     * https://docs.mailtrap.io/inbound-email/receiving-emails
     */
    protected function mockPayload(): string
    {
        return <<<'JSON'
{"events":[{"event":"inbound.message_received","event_id":"00f995a8-6743-55a3-a2eb-bb53eb4704c7","timestamp":1791207458565,"inbox_id":4024,"message_id":"1878217152073113664","from":"Jane Doe \u003cjane@example.com\u003e"},{"event":"inbound.message_received","event_id":"5c0e8f9b-1d2a-4b3c-9e7f-6a5b4c3d2e1f","timestamp":1791207459012,"inbox_id":4024,"message_id":"1878217152073113665","from":"John Doe \u003cjohn@example.com\u003e"},{"event":"delivery","event_id":"bede7236-2284-43d6-a953-1fdcafd0fdbc","timestamp":1728669700,"message_id":"1df37d17-0286-4d8b-8edf-bc4ec5be86e6","sending_stream":"transactional","email":"receiver@example.com","sending_domain_name":"example.com"}]}
JSON;
    }
}
