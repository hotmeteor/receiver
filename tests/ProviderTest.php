<?php

namespace Receiver\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Receiver\Providers\Webhook;
use Receiver\Tests\Fixtures\TestProvider;

class ProviderTest extends TestCase
{
    public function test_handles_webhook_with_existing_handler()
    {
        $request = new Request($this->mockPayload());

        $provider = new TestProvider();

        $response = $provider
            ->receive($request)
            ->fallback(fn (Webhook $webhook) => throw new \Exception('Fallback!'))
            ->ok();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function test_handles_webhook_with_missing_handler()
    {
        $this->expectExceptionMessage('Fallback!');

        $payload = $this->mockPayload();
        data_set($payload, 'event', 'foo.bazzed');

        $request = new Request($payload);

        $provider = new TestProvider();

        $response = $provider
            ->receive($request)
            ->fallback(fn (Webhook $webhook) => throw new \Exception('Fallback!'))
            ->ok();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    protected function mockPayload(string $key = null): mixed
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
