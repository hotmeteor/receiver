<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class ShopifyProvider extends AbstractProvider
{
    /**
     * https://shopify.dev/docs/apps/webhooks/configuration/https#step-5-verify-the-webhook
     */
    public function verify(Request $request): bool
    {
        $header = $request->header('X-Shopify-Hmac-Sha256');
        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $this->secret, true));

        return hash_equals($computed, (string) $header);
    }

    public function getEvent(Request $request): string|array
    {
        return str_replace('/', '_', (string) ($request->header('X-Shopify-Topic') ?? ''));
    }
}
