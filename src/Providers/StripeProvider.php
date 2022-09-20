<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class StripeProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return array|null
     */
    public function handshake(Request $request): array|null
    {
        return $request->has('challenge') ? $request->only('challenge') : null;
    }

    /**
     * https://stripe.com/docs/webhooks/signatures#verify-official-libraries.
     *
     * @param  Request  $request
     * @return bool
     */
    public function verify(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('STRIPE_SIGNATURE');

        try {
            \Stripe\Webhook::constructEvent(
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
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return $request->input('type');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getData(Request $request): array
    {
        return $request->input('data');
    }
}
