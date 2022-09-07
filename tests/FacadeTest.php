<?php

namespace Receiver\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Receiver\Facades\Receiver;

class FacadeTest extends TestCase
{
    public function test_ide_helpers()
    {
        $request = new Request();

        $receiver = Receiver::driver('fake')->receive($request);

        $this->assertInstanceOf(JsonResponse::class, $receiver->ok());
    }
}
