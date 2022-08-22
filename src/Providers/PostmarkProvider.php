<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostmarkProvider extends AbstractProvider
{
    /**
     * https://postmarkapp.com/developer/webhooks/webhooks-overview#protecting-your-webhook.
     *
     * @param  Request  $request
     * @return bool
     */
    public function verify(Request $request): bool
    {
        try {
            Auth::onceBasic();

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return $request->filled('RecordType') ? $request->input('RecordType') : 'Inbound';
    }
}
