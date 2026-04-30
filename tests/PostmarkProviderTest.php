<?php

namespace Receiver\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Receiver\Providers\PostmarkProvider;
use Receiver\Providers\Webhook;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostmarkProviderTest extends TestCase
{
    public function test_it_can_verify_postmark_webhook()
    {
        Auth::shouldReceive('onceBasic')->once()->andReturnNull();

        $request = Mockery::mock(Request::class);
        $request->allows('filled')->with('RecordType')->andReturns(true);
        $request->allows('input')->with('RecordType')->andReturns('Delivery');
        $request->allows('all')->andReturns(['RecordType' => 'Delivery']);

        $provider = new PostmarkProvider(null);
        $provider->receive($request);

        $this->assertInstanceOf(Webhook::class, $provider->webhook());
    }

    public function test_it_denies_unauthorized_postmark_webhook()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        Auth::shouldReceive('onceBasic')->once()->andReturn(new Response('Unauthorized', 401));

        $request = Mockery::mock(Request::class);

        $provider = new PostmarkProvider(null);
        $provider->receive($request);
    }

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
}
