<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class TwilioProvider extends AbstractProvider
{
    /**
     * https://www.twilio.com/docs/usage/webhooks/webhooks-security#validating-signatures-from-twilio
     *
     * Validates form-encoded (non-streaming) Twilio webhooks.
     * Uses the full URL (including query string) and sorted POST parameters.
     */
    public function verify(Request $request): bool
    {
        $header = $request->header('X-Twilio-Signature');

        if (! $header) {
            return false;
        }

        $url = $request->fullUrl();
        $params = $request->post();

        if (! empty($params)) {
            ksort($params);
            foreach ($params as $key => $value) {
                $url .= $key.$value;
            }
        }

        $computed = base64_encode(hash_hmac('sha1', $url, $this->secret, true));

        return hash_equals($computed, $header);
    }

    public function getEvent(Request $request): string|array
    {
        return $request->input('EventType')
            ?? $request->input('MessageStatus')
            ?? $request->input('CallStatus')
            ?? '';
    }
}
