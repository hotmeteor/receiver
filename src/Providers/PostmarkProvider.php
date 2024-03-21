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
        foreach (config('receiver.postmark.verification_types') as $verification_type) {
            switch ($verification_type) {
                case 'auth':
                    try {
                        Auth::onceBasic();
                    } catch (\Exception $exception) {
                        return false;
                    }
                    break;

                case 'headers':
                    foreach (config('receiver.postmark.headers') as $key => $value) {
                        if (!$request->hasHeader($key)) {
                            return false;
                        }

                        if ($request->header($key) !== $value) {
                            return false;
                        }
                    }
                    break;

                case 'ips':
                    if (!in_array($request->getClientIp(), config('receiver.postmark.ips'), true)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
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
