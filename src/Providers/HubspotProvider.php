<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class HubspotProvider extends AbstractProvider
{
    /**
     * https://developers.hubspot.com/docs/api/webhooks/validating-requests#validate-the-v3-request-signature.
     *
     * @param  Request  $request
     * @return bool
     */
    public function verify(Request $request): bool
    {
        if ((now()->unix() - $request->header('X-HubSpot-Request-Timestamp')) < 60 * 5) {
            $header = $request->header('X-HubSpot-Signature-v3');

            $signature = implode('', [
                $request->method(),
                $request->getUri(),
                $request->getContent(),
            ]);

            $signature = urlencode($signature);
            $signature = hash_hmac('sha256', $signature, $this->secret);
            $signature = base64_encode($signature);

            return hash_equals($signature, $header);
        }

        return false;
    }

    /**
     * @param  Request  $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return $request->input('eventType');
    }
}
