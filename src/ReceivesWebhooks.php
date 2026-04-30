<?php

namespace Receiver;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Receiver\Facades\Receiver;

/**
 * @mixin Controller
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
