<?php

namespace Receiver;

use Illuminate\Http\Request;
use Receiver\Facades\Receiver;

/**
 * @mixin \Illuminate\Routing\Controller
 */
trait ReceivesWebhooks
{
    public function store(Request $request, string $provider)
    {
        return Receiver::driver($provider)
            ->receive($request)
            ->ok();
    }
}
