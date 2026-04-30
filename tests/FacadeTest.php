<?php

namespace Receiver\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Receiver\Facades\Receiver;

class FacadeTest extends TestCase
{
    #[Test]
    public function ide_helpers(): void
    {
        $request = new Request;

        $receiver = Receiver::driver('fake')->receive($request);

        $this->assertInstanceOf(JsonResponse::class, $receiver->ok());
    }
}
