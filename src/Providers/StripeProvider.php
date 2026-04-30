<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeProvider extends AbstractProvider
{
    public function handshake(Request $request): ?array
    {
        return $request->has('challenge') ? $request->only('challenge') : null;
    }

    /**
     * https://stripe.com/docs/webhooks/signatures#verify-official-libraries.
     */
    public function verify(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('STRIPE_SIGNATURE');

        try {
            Webhook::constructEvent(
                $payload,
                $signature,
                $this->secret
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getEvent(Request $request): string|array
    {
        return $request->input('type');
    }

    public function getData(Request $request): array
    {
        return $request->input('data');
    }
}
