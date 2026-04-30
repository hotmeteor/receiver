<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class PaddleProvider extends AbstractProvider
{
    /**
     * Paddle Billing webhook signature verification.
     * Header format: Paddle-Signature: ts=<timestamp>;h1=<hmac>[;h1=<hmac2>]
     *
     * Multiple h1 values may be present during secret rotation; a match
     * against any of them is accepted.
     *
     * https://developer.paddle.com/webhooks/signature-verification
     */
    public function verify(Request $request): bool
    {
        $header = $request->header('Paddle-Signature');

        if (! $header) {
            return false;
        }

        $ts = null;
        $signatures = [];

        foreach (explode(';', $header) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key === 'ts') {
                $ts = $value;
            } elseif ($key === 'h1') {
                $signatures[] = $value;
            }
        }

        if (! $ts || empty($signatures)) {
            return false;
        }

        $payload = $ts.':'.$request->getContent();
        $computed = hash_hmac('sha256', $payload, $this->secret);

        foreach ($signatures as $signature) {
            if (hash_equals($computed, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function getEvent(Request $request): string|array
    {
        return (string) $request->input('event_type', '');
    }
}
