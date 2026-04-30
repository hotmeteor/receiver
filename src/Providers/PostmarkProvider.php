<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostmarkProvider extends AbstractProvider
{
    /**
     * Verify the incoming Postmark webhook request.
     *
     * Configure which verification methods to run (and in what order) via:
     * config('services.postmark.webhook.verification_types')
     *
     * Available types:
     *   - 'auth'    Verify via HTTP Basic Auth (Auth::onceBasic)
     *   - 'headers' Verify that specific headers are present and match expected values
     *   - 'ips'     Verify that the request originates from an allowed IP address
     *
     * https://postmarkapp.com/developer/webhooks/webhooks-overview#protecting-your-webhook.
     */
    public function verify(Request $request): bool
    {
        foreach (config('services.postmark.webhook.verification_types') ?? [] as $type) {
            switch ($type) {
                case 'auth':
                    if (Auth::onceBasic() !== null) {
                        return false;
                    }
                    break;

                case 'headers':
                    foreach (config('services.postmark.webhook.headers') ?? [] as $key => $value) {
                        if (! $request->hasHeader($key) || $request->header($key) !== $value) {
                            return false;
                        }
                    }
                    break;

                case 'ips':
                    $allowed = config('services.postmark.webhook.ips') ?? [];
                    if (! in_array($request->getClientIp(), $allowed, true)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getEvent(Request $request): string|array
    {
        return $request->filled('RecordType') ? $request->input('RecordType') : 'Inbound';
    }
}
