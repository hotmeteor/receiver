<?php

namespace Receiver\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Providers\Webhook;
use Receiver\Tests\Fixtures\TestProvider;

class ProviderTest extends TestCase
{
    #[Test]
    public function handles_webhook_with_existing_handler(): void
    {
        $request = new Request($this->mockPayload());

        $provider = new TestProvider;

        $response = $provider
            ->receive($request)
            ->fallback(fn (Webhook $webhook) => throw new \Exception('Fallback!'))
            ->ok();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[Test]
    public function handles_webhook_with_missing_handler(): void
    {
        $this->expectExceptionMessage('Fallback!');

        $payload = $this->mockPayload();
        data_set($payload, 'event', 'foo.bazzed');

        $request = new Request($payload);

        $provider = new TestProvider;

        $response = $provider
            ->receive($request)
            ->fallback(fn (Webhook $webhook) => throw new \Exception('Fallback!'))
            ->ok();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[Test]
    public function handles_multiple_events_in_single_payload(): void
    {
        $events = [
            'event_a' => ['id' => 1],
            'event_b' => ['id' => 2],
        ];

        $request = new Request(['event' => $events, 'data' => []]);

        $provider = new TestProvider;

        $response = $provider
            ->receive($request)
            ->ok();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue($provider->dispatched());
        $this->assertTrue($provider->dispatched(Fixtures\EventA::class));
        $this->assertTrue($provider->dispatched(Fixtures\EventB::class));
    }

    #[Test]
    public function handler_class_resolved_case_insensitively(): void
    {
        // 'FOO.BARRED' and 'foo.barred' must resolve to the same class
        $payload = $this->mockPayload();
        data_set($payload, 'event', 'FOO.BARRED');

        $request = new Request($payload);

        $provider = new TestProvider;

        $response = $provider
            ->receive($request)
            ->ok();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue($provider->dispatched());
    }

    protected function mockPayload(?string $key = null): mixed
    {
        $payload = [
            'event' => 'foo.barred',
            'data' => [
                'id' => 1,
                'name' => 'Test',
                'email' => 'test@test.test',
            ],
        ];

        return $key ? data_get($payload, $key) : $payload;
    }
}
